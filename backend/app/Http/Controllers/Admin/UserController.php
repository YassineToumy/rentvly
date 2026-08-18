<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->withCount('estimations');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $results = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => collect($results->items())->map(fn (User $u) => $this->transform($u)),
            'meta'    => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('estimations');

        return response()->json([
            'success' => true,
            'data'    => $this->transform($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role'     => ['required', Rule::in(['admin', 'investor'])],
        ]);

        $user = User::create($validated);
        $user->loadCount('estimations');

        return response()->json([
            'success' => true,
            'data'    => $this->transform($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', Password::min(8)],
            'role'     => ['sometimes', 'required', Rule::in(['admin', 'investor'])],
        ]);

        // Prevent demoting the last admin
        if (isset($validated['role']) && $validated['role'] !== 'admin' && $user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Impossible de retirer le dernier administrateur.',
                ], 422);
            }
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->loadCount('estimations');

        return response()->json([
            'success' => true,
            'data'    => $this->transform($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'error'   => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'error'   => 'Impossible de supprimer le dernier administrateur.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé.',
        ]);
    }

    private function transform(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'role'              => $user->role,
            'estimations_count' => $user->estimations_count ?? 0,
            'created_at'        => $user->created_at?->toISOString(),
            'updated_at'        => $user->updated_at?->toISOString(),
        ];
    }
}
