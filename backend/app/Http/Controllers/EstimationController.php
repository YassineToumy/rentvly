<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstimationRequest;
use App\Http\Requests\UpdateEstimationRequest;
use App\Models\Estimation;
use App\Services\InvestmentProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimationController extends Controller
{
    public function __construct(
        private readonly InvestmentProjectionService $projections
    ) {}

    /**
     * GET /api/v1/estimations — list + dashboard stats (racines uniquement)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $estimations = $user->estimations()->roots()->withCount('variants')->latest()->get();

        $withYield = $estimations->filter(fn ($e) => $e->net_yield !== null);
        $purchased = $estimations->filter(fn ($e) => $e->is_purchased);

        $portfolioInvested = $purchased->sum(fn ($e) => (float) ($e->latestPurchasePrice() ?? $e->purchase_price ?? 0));

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total' => $estimations->count(),
                    'purchased_count' => $purchased->count(),
                    'portfolio_invested' => $portfolioInvested > 0 ? round($portfolioInvested, 2) : null,
                    'average_net_yield' => $withYield->isNotEmpty()
                        ? round($withYield->avg('net_yield'), 2)
                        : null,
                    'cities_count' => $estimations->pluck('city')->filter()->unique()->count(),
                ],
                'estimations' => $estimations->map(fn ($e) => $this->listItem($e)),
                'purchased' => $purchased->map(fn ($e) => $this->purchasedItem($e))->values(),
            ],
        ]);
    }

    /**
     * GET /api/v1/estimations/by-listing/{listingId}
     */
    public function byListing(Request $request, string $listingId): JsonResponse
    {
        $main = $request->user()->estimations()
            ->roots()
            ->where('listing_id', $listingId)
            ->with('variants')
            ->first();

        if (! $main) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $main->id,
                'listing_id' => $main->listing_id,
                'purchase_price' => $main->purchase_price,
                'latest_purchase_price' => $main->latestPurchasePrice(),
                'variants_count' => $main->variants->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/estimations
     */
    public function store(StoreEstimationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $listingId = $validated['listing_id'] ?? null;

        if ($listingId) {
            $existing = $request->user()->estimations()
                ->roots()
                ->where('listing_id', $listingId)
                ->first();

            if ($existing) {
                return $this->storeForExistingListing($request, $validated, $existing);
            }
        }

        $estimation = $this->createEstimation($request->user()->id, $validated, null, $listingId);

        return response()->json([
            'success' => true,
            'save_action' => 'created',
            'data' => $this->detail($estimation),
            'message' => 'Estimation enregistrée.',
        ], 201);
    }

    /**
     * GET /api/v1/estimations/{estimation}
     */
    public function show(Request $request, Estimation $estimation): JsonResponse
    {
        $this->authorizeEstimation($request, $estimation);

        $root = $estimation->isRoot() ? $estimation : $estimation->parent;
        if (! $root) {
            abort(404);
        }

        $root->load('variants');

        return response()->json([
            'success' => true,
            'data' => $this->detail($root),
        ]);
    }

    /**
     * PATCH /api/v1/estimations/{estimation}
     */
    public function update(UpdateEstimationRequest $request, Estimation $estimation): JsonResponse
    {
        $this->authorizeEstimation($request, $estimation);

        if (! $estimation->isRoot()) {
            return response()->json([
                'success' => false,
                'error' => 'Seule l\'estimation principale peut être modifiée.',
            ], 422);
        }

        $validated = $request->validated();

        if (array_key_exists('purchase_price', $validated) && $validated['purchase_price'] !== null) {
            $estimation->purchase_price = $validated['purchase_price'];
        }

        if (array_key_exists('is_purchased', $validated)) {
            $markingPurchased = (bool) $validated['is_purchased'];

            if ($markingPurchased && ! $estimation->purchase_price && ! $estimation->latestPurchasePrice()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Un prix d\'achat est requis pour marquer le bien comme acheté.',
                ], 422);
            }

            $estimation->is_purchased = $markingPurchased;
            $estimation->purchased_at = $markingPurchased ? now() : null;
        }

        $estimation->save();

        return response()->json([
            'success' => true,
            'data' => $this->detail($estimation->fresh()->load('variants')),
            'message' => $estimation->is_purchased
                ? 'Bien marqué comme acheté.'
                : 'Statut d\'achat retiré.',
        ]);
    }

    /**
     * DELETE /api/v1/estimations/{estimation}
     */
    public function destroy(Request $request, Estimation $estimation): JsonResponse
    {
        $this->authorizeEstimation($request, $estimation);

        if ($estimation->isRoot()) {
            $estimation->delete();
        } else {
            $estimation->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Estimation supprimée.',
        ]);
    }

    private function storeForExistingListing(Request $request, array $validated, Estimation $main): JsonResponse
    {
        $newPrice = $this->resolvePurchasePrice($validated);
        $lastPrice = $main->latestPurchasePrice();

        if ($newPrice > 0 && $lastPrice !== null && abs($newPrice - $lastPrice) > 0.01) {
            $variant = $this->createEstimation(
                $request->user()->id,
                $validated,
                $main->id,
                $main->listing_id
            );

            $main->load('variants');

            return response()->json([
                'success' => true,
                'save_action' => 'variant_created',
                'data' => $this->detail($main),
                'variant' => $this->variantItem($variant),
                'message' => 'Nouvelle sous-estimation ajoutée (prix modifié).',
            ], 201);
        }

        if ($newPrice > 0 && ($lastPrice === null || $lastPrice <= 0)) {
            $main->purchase_price = $newPrice;
            $main->net_yield = $validated['rentability']['net_yield'] ?? $main->net_yield;
            $main->rentability = $validated['rentability'] ?? $main->rentability;
            $main->prediction = $validated['prediction'];
            $main->predicted_rent = $validated['prediction']['predicted_rent'];
            $main->save();
            $main->load('variants');

            return response()->json([
                'success' => true,
                'save_action' => 'updated',
                'data' => $this->detail($main),
                'message' => 'Estimation mise à jour.',
            ]);
        }

        $main->load('variants');

        return response()->json([
            'success' => true,
            'save_action' => 'already_exists',
            'data' => $this->detail($main),
            'message' => 'Ce bien est déjà dans votre tableau de bord. Modifiez le prix d\'achat pour créer une sous-estimation.',
        ], 200);
    }

    private function createEstimation(int $userId, array $validated, ?int $parentId, ?string $listingId): Estimation
    {
        $form = $validated['form'];
        $prediction = $validated['prediction'];
        $rentability = $validated['rentability'] ?? null;

        return Estimation::create([
            'user_id' => $userId,
            'listing_id' => $listingId,
            'parent_id' => $parentId,
            'city' => $form['city'] ?? '',
            'postal_code' => $form['postal_code'] ?? '',
            'district_name' => $form['district_name'] ?? null,
            'property_type' => $form['property_type'] ?? 'flat',
            'surface_area' => isset($form['surface_area']) ? (int) $form['surface_area'] : null,
            'rooms' => (int) ($form['rooms'] ?? 1),
            'predicted_rent' => $prediction['predicted_rent'],
            'purchase_price' => $this->resolvePurchasePrice($validated) ?: null,
            'net_yield' => $rentability['net_yield'] ?? null,
            'form_data' => $form,
            'prediction' => $prediction,
            'rentability' => $rentability,
        ]);
    }

    private function resolvePurchasePrice(array $validated): float
    {
        return (float) ($validated['purchase_price'] ?? ($validated['rentability']['purchase_price'] ?? 0));
    }

    private function authorizeEstimation(Request $request, Estimation $estimation): void
    {
        if ($estimation->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    private function listItem(Estimation $e): array
    {
        return [
            'id' => $e->id,
            'listing_id' => $e->listing_id,
            'city' => $e->city,
            'postal_code' => $e->postal_code,
            'district_name' => $e->district_name,
            'property_type' => $e->property_type,
            'property_type_label' => $e->property_type === 'house' ? 'Maison' : 'Appartement',
            'surface_area' => $e->surface_area,
            'rooms' => $e->rooms,
            'predicted_rent' => $e->predicted_rent,
            'net_yield' => $e->net_yield,
            'purchase_price' => $e->purchase_price,
            'latest_purchase_price' => $e->latestPurchasePrice(),
            'is_purchased' => (bool) $e->is_purchased,
            'purchased_at' => $e->purchased_at?->toISOString(),
            'variants_count' => (int) ($e->variants_count ?? $e->variants()->count()),
            'created_at' => $e->created_at->toISOString(),
        ];
    }

    private function variantItem(Estimation $e): array
    {
        return [
            'id' => $e->id,
            'parent_id' => $e->parent_id,
            'purchase_price' => $e->purchase_price,
            'predicted_rent' => $e->predicted_rent,
            'net_yield' => $e->net_yield,
            'prediction' => $e->prediction,
            'rentability' => $e->rentability,
            'created_at' => $e->created_at->toISOString(),
        ];
    }

    private function purchasedItem(Estimation $e): array
    {
        return array_merge($this->listItem($e), [
            'investment' => $this->projections->forEstimation($e),
        ]);
    }

    private function detail(Estimation $e): array
    {
        $e->loadMissing('variants');

        $data = array_merge($this->listItem($e), [
            'form' => $e->form_data,
            'prediction' => $e->prediction,
            'rentability' => $e->rentability,
            'variants' => $e->variants->map(fn ($v) => $this->variantItem($v))->values(),
        ]);

        if ($e->is_purchased) {
            $data['investment'] = $this->projections->forEstimation($e);
        }

        return $data;
    }
}
