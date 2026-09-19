<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vente::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%")
                    ->orWhere('postal_code', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('property_type', $type);
        }

        if ($min = $request->input('min_price')) {
            $query->where('price', '>=', (int) $min);
        }
        if ($max = $request->input('max_price')) {
            $query->where('price', '<=', (int) $max);
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $results = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => collect($results->items())->map(fn (Vente $v) => $this->transform($v)),
            'meta'    => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $vente = Vente::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->transformFull($vente),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $vente = Vente::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $this->transformFull($vente),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vente = Vente::findOrFail($id);
        $validated = $this->validatePayload($request, partial: true);
        $vente->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $this->transformFull($vente->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $vente = Vente::findOrFail($id);
        $vente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Annonce supprimée.',
        ]);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        $validated = $request->validate([
            'title'           => "$req|string|max:500",
            'description'     => 'nullable|string',
            'property_type'   => "$req|in:flat,house",
            'price'           => "$req|integer|min:0",
            'price_per_sqm'   => 'nullable|numeric|min:0',
            'surface_area'    => 'nullable|numeric|min:0',
            'rooms_quantity'  => 'nullable|integer|min:0',
            'city'            => "$req|string|max:255",
            'postal_code'     => 'nullable|string|max:20',
            'department_code' => 'nullable|string|max:10',
            'district_name'   => 'nullable|string|max:255',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            'owner_name'      => 'nullable|string|max:255',
            'owner_type'      => 'nullable|string|max:100',
            'is_new_property' => 'sometimes|boolean',
            'is_pro'          => 'sometimes|boolean',
            'photos'          => 'nullable|array',
            'photos.*'        => 'nullable|string|max:2000',
            'external_id'     => 'nullable|string|max:255',
        ]);

        if (array_key_exists('photos', $validated)) {
            $validated['photos_count'] = count($validated['photos'] ?? []);
        }

        return $validated;
    }

    private function transform(Vente $v): array
    {
        return [
            'id'              => $v->id,
            'external_id'     => $v->external_id,
            'title'           => $v->title,
            'city'            => $v->city,
            'postal_code'     => $v->postal_code,
            'property_type'   => $v->property_type,
            'price'           => $v->price,
            'price_per_sqm'   => $v->price_per_sqm,
            'surface_area'    => $v->surface_area,
            'rooms_quantity'  => $v->rooms_quantity,
            'is_new_property' => $v->is_new_property,
            'is_pro'          => $v->is_pro,
            'image'           => ($v->photos ?? [])[0] ?? null,
            'photos_count'    => $v->photos_count ?? count($v->photos ?? []),
            'publication_date'=> $v->publication_date,
            'created_at'      => $v->created_at,
            'updated_at'      => $v->updated_at,
        ];
    }

    private function transformFull(Vente $v): array
    {
        return array_merge($this->transform($v), [
            'description'     => $v->description,
            'department_code' => $v->department_code,
            'district_name'   => $v->district_name,
            'latitude'        => $v->latitude,
            'longitude'       => $v->longitude,
            'owner_name'      => $v->owner_name,
            'owner_type'      => $v->owner_type,
            'photos'          => $v->photos ?? [],
            'interior_features' => $v->interior_features,
            'exterior_features' => $v->exterior_features,
            'other_features'  => $v->other_features,
        ]);
    }
}
