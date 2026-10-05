<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const RESET_CODE_TTL_MINUTES = 15;
    private const VERIFY_CODE_TTL_MINUTES = 15;

    /**
     * POST /api/v1/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => ['required', 'confirmed', Password::min(8)],
        ], [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required'  => 'Le nom est obligatoire.',
            'email.required'      => "L'email est obligatoire.",
            'email.unique'        => 'Cet email est déjà utilisé.',
            'password.required'   => 'Le mot de passe est obligatoire.',
            'password.confirmed'  => 'Les mots de passe ne correspondent pas.',
            'password.min'        => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $user = User::create([
            'name'     => $validated['first_name'].' '.$validated['last_name'],
            'email'    => $validated['email'],
            // 'password' is cast as 'hashed' on the User model — pass plain text
            'password' => $validated['password'],
            'role'     => 'investor',
            // Compte non vérifié : un code 2FA doit être validé avant d'obtenir un token
            'email_verified_at' => null,
        ]);

        try {
            $this->sendVerificationCode($user);
        } catch (\Throwable $e) {
            report($e);

            $message = "Compte créé, mais l'envoi du code a échoué. Réessayez via « Renvoyer le code ».";
            if (config('app.debug')) {
                $message .= ' ('.$e->getMessage().')';
            }

            return response()->json([
                'success'              => false,
                'requires_verification' => true,
                'email'                => $user->email,
                'error'                => $message,
            ], 500);
        }

        return response()->json([
            'success'              => true,
            'requires_verification' => true,
            'email'                => $user->email,
            'message'              => 'Un code de vérification vient d’être envoyé à votre adresse e-mail.',
        ], 201);
    }

    /**
     * POST /api/v1/verify-email
     * Valide le code 2FA envoyé à l'inscription puis délivre le token.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ], [
            'email.required' => "L'email est obligatoire.",
            'code.required'  => 'Le code est obligatoire.',
            'code.size'      => 'Le code doit contenir 6 chiffres.',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $this->isValidVerificationCode($user->email, $validated['code'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Code invalide ou expiré.',
            ], 422);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        DB::table('email_verification_codes')->where('email', $user->email)->delete();

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte vérifié avec succès.',
            'data'    => [
                'user'  => $this->userResponse($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * POST /api/v1/resend-verification-code
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => "L'email est obligatoire.",
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        $generic = [
            'success' => true,
            'message' => 'Si un compte non vérifié existe avec cet e-mail, un nouveau code vient d’être envoyé.',
        ];

        if (! $user || $user->email_verified_at !== null) {
            return response()->json($generic);
        }

        try {
            $this->sendVerificationCode($user);
        } catch (\Throwable $e) {
            report($e);

            $message = "Impossible d'envoyer l'e-mail pour le moment. Réessayez plus tard.";
            if (config('app.debug')) {
                $message .= ' ('.$e->getMessage().')';
            }

            return response()->json([
                'success' => false,
                'error'   => $message,
            ], 500);
        }

        return response()->json($generic);
    }

    /**
     * POST /api/v1/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => "L'email est obligatoire.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        if (! Auth::attempt($validated)) {
            return response()->json([
                'success' => false,
                'error'   => 'Email ou mot de passe incorrect.',
            ], 401);
        }

        $user = Auth::user();

        // Compte non vérifié : renvoyer un code 2FA au lieu d'un token
        if ($user->email_verified_at === null) {
            try {
                $this->sendVerificationCode($user);
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'success'               => false,
                'requires_verification' => true,
                'email'                 => $user->email,
                'error'                 => 'Compte non vérifié. Un nouveau code vient d’être envoyé à votre e-mail.',
            ], 403);
        }

        // Delete old tokens (optional: single device login)
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => $this->userResponse($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * POST /api/v1/forgot-password
     * Envoie un code OTP à 6 chiffres par e-mail.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => "L'email est obligatoire.",
            'email.email'    => "L'email n'est pas valide.",
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        // Toujours le même message (pas d'énumération d'emails)
        $generic = [
            'success' => true,
            'message' => 'Si un compte existe avec cet e-mail, un code de vérification vient d’être envoyé.',
        ];

        if (! $user) {
            return response()->json($generic);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => Hash::make($code),
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($user->email)->send(new PasswordResetCodeMail($code, $user->name));
        } catch (\Throwable $e) {
            report($e);

            $message = "Impossible d'envoyer l'e-mail pour le moment. Réessayez plus tard.";
            if (config('app.debug')) {
                $message .= ' ('.$e->getMessage().')';
            }

            return response()->json([
                'success' => false,
                'error'   => $message,
            ], 500);
        }

        return response()->json($generic);
    }

    /**
     * POST /api/v1/verify-reset-code
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ], [
            'email.required' => "L'email est obligatoire.",
            'code.required'  => 'Le code est obligatoire.',
            'code.size'      => 'Le code doit contenir 6 chiffres.',
        ]);

        if (! $this->isValidResetCode($validated['email'], $validated['code'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Code invalide ou expiré.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code valide. Vous pouvez choisir un nouveau mot de passe.',
        ]);
    }

    /**
     * POST /api/v1/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'code'     => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.required'     => "L'email est obligatoire.",
            'code.required'      => 'Le code est obligatoire.',
            'code.size'          => 'Le code doit contenir 6 chiffres.',
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $email = strtolower(trim($validated['email']));

        if (! $this->isValidResetCode($email, $validated['code'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Code invalide ou expiré.',
            ], 422);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => 'Compte introuvable.',
            ], 404);
        }

        $user->password = $validated['password'];
        $user->save();
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour. Vous pouvez vous connecter.',
        ]);
    }

    /**
     * POST /api/v1/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->userResponse($request->user(), $request),
        ]);
    }

    /**
     * PATCH /api/v1/me
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required'  => 'Le nom est obligatoire.',
            'email.required' => "L'email est obligatoire.",
            'email.email'    => "L'email n'est pas valide.",
            'email.unique'   => 'Cet email est déjà utilisé.',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = strtolower(trim($validated['email']));
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour.',
            'data'    => $this->userResponse($user->fresh(), $request),
        ]);
    }

    /**
     * PATCH /api/v1/me/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'password.required'         => 'Le nouveau mot de passe est obligatoire.',
            'password.confirmed'        => 'Les mots de passe ne correspondent pas.',
            'password.min'              => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error'   => 'Mot de passe actuel incorrect.',
            ], 422);
        }

        $user->password = $validated['password'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour.',
        ]);
    }

    /**
     * POST /api/v1/me/avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ], [
            'avatar.required' => 'La photo est obligatoire.',
            'avatar.image'    => 'Le fichier doit être une image.',
            'avatar.mimes'    => 'Formats acceptés : JPEG, PNG, WebP.',
            'avatar.max'      => 'La photo ne doit pas dépasser 2 Mo.',
        ]);

        $this->deleteAvatarFile($user->avatar);

        $file = $request->file('avatar');
        $filename = $user->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $dir = public_path('avatars');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $filename);

        $user->avatar = 'avatars/'.$filename;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Photo de profil mise à jour.',
            'data'    => $this->userResponse($user->fresh(), $request),
        ]);
    }

    private function sendVerificationCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => Hash::make($code),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->send(new EmailVerificationCodeMail($code, $user->name));
    }

    private function isValidVerificationCode(string $email, string $code): bool
    {
        $record = DB::table('email_verification_codes')->where('email', $email)->first();
        if (! $record || ! $record->created_at) {
            return false;
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::VERIFY_CODE_TTL_MINUTES)->isPast()) {
            return false;
        }

        return Hash::check($code, $record->token);
    }

    private function isValidResetCode(string $email, string $code): bool
    {
        $email = strtolower(trim($email));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return false;
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        if (! $record || ! $record->created_at) {
            return false;
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::RESET_CODE_TTL_MINUTES)->isPast()) {
            return false;
        }

        return Hash::check($code, $record->token);
    }

    private function deleteAvatarFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $publicFile = public_path($path);
        if (is_file($publicFile)) {
            @unlink($publicFile);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function avatarUrl(?string $path, ?Request $request = null): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = '/'.ltrim($path, '/');

        // Prefer the host that served the API (Docker :8080, Herd, etc.)
        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/').$relative;
        }

        return rtrim((string) config('app.url'), '/').$relative;
    }

    private function userResponse(User $user, ?Request $request = null): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role ?? 'investor',
            'avatar'     => $user->avatar,
            'avatar_url' => $this->avatarUrl($user->avatar, $request),
            'created_at' => $user->created_at->toISOString(),
        ];
    }
}
