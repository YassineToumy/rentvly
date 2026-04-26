<?php

namespace App\Http\Controllers;

use App\Models\Vente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenteController extends Controller
{
    /**
     * GET /api/v1/ventes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vente::with(['departement', 'region']);

        // Type filter
        if ($type = $request->input('type')) {
            $query->where('property_type', $type);
        }

        // City / postal code search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('city', 'ilike', "%{$search}%")
                  ->orWhere('postal_code', 'like', "%{$search}%");
            });
        }

        // Price range
        if ($min = $request->input('min_price')) {
            $query->where('price', '>=', (int) $min);
        }
        if ($max = $request->input('max_price')) {
            $query->where('price', '<=', (int) $max);
        }

        // Surface
        if ($minS = $request->input('min_surface')) {
            $query->where('surface_area', '>=', (float) $minS);
        }
        if ($maxS = $request->input('max_surface')) {
            $query->where('surface_area', '<=', (float) $maxS);
        }

        // Rooms
        if ($rooms = $request->input('rooms')) {
            $query->where('rooms_quantity', (int) $rooms);
        }

        // Department
        if ($dept = $request->input('department')) {
            $query->where('department_code', $dept);
        }

        // New property only
        if ($request->boolean('is_new')) {
            $query->where('is_new_property', true);
        }

        // JSONB feature filters
        if ($request->boolean('has_garden')) {
            $query->whereJsonContains('exterior_features->has_garden', true);
        }
        if ($request->boolean('has_pool')) {
            $query->whereJsonContains('exterior_features->has_pool', true);
        }
        if ($request->boolean('has_elevator')) {
            $query->whereJsonContains('interior_features->has_elevator', true);
        }
        if ($request->boolean('has_parking')) {
            $query->whereJsonContains('other_features->has_parking', true);
        }

        // Sort
        match ($request->input('sort', 'recent')) {
            'price-asc'  => $query->orderBy('price', 'asc'),
            'price-desc' => $query->orderBy('price', 'desc'),
            'surface'    => $query->orderBy('surface_area', 'desc'),
            default      => $query->orderBy('publication_date', 'desc'),
        };

        // Paginate
        $perPage = min((int) $request->input('per_page', 12), 50);
        $results = $query->paginate($perPage);

        $items = collect($results->items())->map(fn ($v) => $this->transform($v));

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/ventes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $vente = Vente::with(['commune', 'departement', 'region'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->transformFull($vente),
        ]);
    }

    /**
     * Slim transform for listing cards.
     */
    private function transform(Vente $v): array
    {
        $interior = $v->interior_features ?? [];
        $exterior = $v->exterior_features ?? [];
        $other    = $v->other_features ?? [];

        return [
            'id'                  => $v->id,
            'external_id'         => $v->external_id,
            'title'               => $v->title,
            'city'                => $v->city,
            'postal_code'         => $v->postal_code,
            'department_code'     => $v->department_code,
            'department_name'     => $v->departement?->nom,
            'region_name'         => $v->region?->nom,
            'district_name'       => $v->district_name,
            'property_type'       => $v->property_type,
            'is_new_property'     => $v->is_new_property,
            'price'               => $v->price,
            'price_per_sqm'       => $v->price_per_sqm,
            'price_has_decreased' => $v->price_has_decreased,
            'surface_area'        => $v->surface_area,
            'rooms_quantity'      => $v->rooms_quantity,
            'equipment_score'     => $v->equipment_score,
            'has_elevator'        => $interior['has_elevator'] ?? false,
            'has_garden'          => $exterior['has_garden'] ?? false,
            'has_terrace'         => $exterior['has_terrace'] ?? false,
            'has_balcony'         => $exterior['has_balcony'] ?? false,
            'has_pool'            => $exterior['has_pool'] ?? false,
            'has_parking'         => $other['has_parking'] ?? false,
            'image'               => ($v->photos ?? [])[0] ?? null,
            'images_count'        => $v->photos_count,
            'owner_name'          => $v->owner_name,
            'owner_type'          => $v->owner_type,
            'is_pro'              => $v->is_pro,
            'publication_date'    => $v->publication_date,
        ];
    }

    /**
     * Full transform for detail page.
     */
    private function transformFull(Vente $v): array
    {
        return array_merge($this->transform($v), [
            'description'        => $v->description,
            'commune_name'       => $v->commune?->nom,
            'code_commune'       => $v->code_commune,
            'code_region'        => $v->code_region,
            'latitude'           => $v->latitude,
            'longitude'          => $v->longitude,
            'code_insee'         => $v->code_insee,
            'reduced_vat'        => $v->reduced_vat,
            'surface_per_room'   => $v->surface_per_room,
            'photos'             => $v->photos,
            'delivery_date'      => $v->delivery_date,
            'interior_features'  => $v->interior_features,
            'exterior_features'  => $v->exterior_features,
            'other_features'     => $v->other_features,
            'modification_date'  => $v->modification_date,
            'scraped_at'         => $v->scraped_at,
        ]);
    }
}