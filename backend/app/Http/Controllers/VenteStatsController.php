<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Region;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenteStatsController extends Controller
{
    /**
     * GET /api/v1/ventes/stats
     */
    public function index(Request $request): JsonResponse
    {
        $base = $this->filteredQuery($request);
        $minCity = max(5, min(50, (int) $request->input('min_listings', 10)));

        $overview = (clone $base)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(AVG(ventes.price), 0) as avg_price')
            ->selectRaw('COALESCE(PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY ventes.price), 0) as median_price')
            ->selectRaw('COALESCE(AVG(ventes.price_per_sqm), 0) as avg_price_m2')
            ->selectRaw('COALESCE(AVG(ventes.surface_area), 0) as avg_surface')
            ->selectRaw('COALESCE(AVG(ventes.rooms_quantity), 0) as avg_rooms')
            ->selectRaw('COALESCE(MIN(ventes.price), 0) as min_price')
            ->selectRaw('COALESCE(MAX(ventes.price), 0) as max_price')
            ->first();

        $nationalPpm2 = (float) ($overview->avg_price_m2 ?? 0);
        $cities = $this->cityStats($request, $minCity, $nationalPpm2);

        $byType = (clone $base)
            ->select('ventes.property_type')
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->selectRaw('COALESCE(AVG(ventes.price_per_sqm), 0)::int as avg_price_m2')
            ->groupBy('ventes.property_type')
            ->orderByDesc('listings')
            ->get();

        $priceBuckets = $this->priceBuckets($base);
        $priceCurve = $this->priceCurve($base);
        $surfaceBuckets = $this->surfaceBuckets($base);
        $rooms = $this->roomsDistribution($base);
        $timeline = $this->timeline($base);

        $departments = (clone $base)
            ->leftJoin('departements', 'departements.code_departement', '=', 'ventes.department_code')
            ->select('ventes.department_code')
            ->selectRaw('MIN(departements.nom) as department_name')
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->selectRaw('COALESCE(AVG(ventes.price_per_sqm), 0)::int as avg_price_m2')
            ->whereNotNull('ventes.department_code')
            ->groupBy('ventes.department_code')
            ->havingRaw('COUNT(*) >= 5')
            ->orderByDesc('avg_price')
            ->limit(20)
            ->get();

        $regions = (clone $base)
            ->leftJoin('regions', 'regions.code_region', '=', 'ventes.code_region')
            ->select('ventes.code_region')
            ->selectRaw('MIN(regions.nom) as region_name')
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->selectRaw('COALESCE(AVG(ventes.price_per_sqm), 0)::int as avg_price_m2')
            ->whereNotNull('ventes.code_region')
            ->groupBy('ventes.code_region')
            ->havingRaw('COUNT(*) >= 5')
            ->orderByDesc('listings')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'overview' => [
                    'total'        => (int) $overview->total,
                    'avg_price'    => (int) round($overview->avg_price),
                    'median_price' => (int) round($overview->median_price),
                    'avg_price_m2' => (int) round($overview->avg_price_m2),
                    'avg_surface'  => round((float) $overview->avg_surface, 1),
                    'avg_rooms'    => round((float) $overview->avg_rooms, 1),
                    'min_price'    => (int) $overview->min_price,
                    'max_price'    => (int) $overview->max_price,
                ],
                'cities' => [
                    'most_expensive'   => $cities->sortByDesc('avg_price')->take(10)->values(),
                    'highest_price_m2' => $cities->sortByDesc('avg_price_m2')->take(10)->values(),
                    'most_rentable'    => $cities->sortByDesc('yield_index')->take(10)->values(),
                    'most_listings'    => $cities->sortByDesc('listings')->take(10)->values(),
                ],
                'by_type'         => $byType,
                'price_buckets'   => $priceBuckets,
                'price_curve'     => $priceCurve,
                'surface_buckets' => $surfaceBuckets,
                'rooms'           => $rooms,
                'timeline'        => $timeline,
                'departments'     => $departments,
                'regions'         => $regions,
                'filter_options'  => $this->filterOptions($request),
            ],
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        // Always qualify ventes.* — joins (departements, regions) also expose code_region.
        return Vente::query()
            ->when($request->input('type'), fn ($q, $t) => $q->where('ventes.property_type', $t))
            ->when($request->input('region'), fn ($q, $r) => $q->where('ventes.code_region', $r))
            ->when($request->input('department'), fn ($q, $d) => $q->where('ventes.department_code', $d))
            ->when($request->input('city'), function ($q, $c) {
                $q->where('ventes.city', 'ilike', trim($c));
            })
            ->when($request->filled('min_price'), fn ($q) => $q->where('ventes.price', '>=', (int) $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('ventes.price', '<=', (int) $request->input('max_price')))
            ->when($request->filled('min_surface'), fn ($q) => $q->where('ventes.surface_area', '>=', (float) $request->input('min_surface')))
            ->when($request->filled('max_surface'), fn ($q) => $q->where('ventes.surface_area', '<=', (float) $request->input('max_surface')));
    }

    private function filterOptions(Request $request): array
    {
        $regions = Region::query()
            ->orderBy('nom')
            ->get(['code_region', 'nom'])
            ->map(fn ($r) => ['value' => $r->code_region, 'label' => $r->nom]);

        $departments = Departement::query()
            ->when($request->input('region'), fn ($q, $r) => $q->where('code_region', $r))
            ->orderBy('nom')
            ->get(['code_departement', 'nom', 'code_region'])
            ->map(fn ($d) => [
                'value'  => $d->code_departement,
                'label'  => $d->nom.' ('.$d->code_departement.')',
                'region' => $d->code_region,
            ]);

        $citiesQuery = Vente::query()
            ->when($request->input('type'), fn ($q, $t) => $q->where('ventes.property_type', $t))
            ->when($request->input('region'), fn ($q, $r) => $q->where('ventes.code_region', $r))
            ->when($request->input('department'), fn ($q, $d) => $q->where('ventes.department_code', $d))
            ->whereNotNull('ventes.city')
            ->where('ventes.city', '!=', '')
            ->selectRaw('MIN(ventes.city) as city')
            ->selectRaw('COUNT(*) as listings')
            ->groupByRaw('LOWER(TRIM(ventes.city))')
            ->orderByDesc('listings')
            ->limit(80)
            ->get()
            ->map(fn ($c) => [
                'value' => $c->city,
                'label' => $c->city.' ('.$c->listings.')',
            ]);

        return [
            'regions'     => $regions,
            'departments' => $departments,
            'cities'      => $citiesQuery,
        ];
    }

    private function cityStats(Request $request, int $minCity, float $nationalPpm2)
    {
        $rows = $this->filteredQuery($request)
            ->selectRaw('MIN(ventes.city) as city')
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->selectRaw('COALESCE(AVG(ventes.price_per_sqm), 0)::int as avg_price_m2')
            ->selectRaw('COALESCE(AVG(ventes.surface_area), 0) as avg_surface')
            ->selectRaw('MIN(ventes.price) as min_price')
            ->selectRaw('MAX(ventes.price) as max_price')
            ->whereNotNull('ventes.city')
            ->where('ventes.city', '!=', '')
            ->where('ventes.price_per_sqm', '>', 0)
            ->groupByRaw('LOWER(TRIM(ventes.city))')
            ->havingRaw('COUNT(*) >= ?', [$minCity])
            ->get();

        return $rows->map(function ($row) use ($nationalPpm2) {
            $ppm2 = (int) $row->avg_price_m2;
            $yieldIndex = $ppm2 > 0 && $nationalPpm2 > 0
                ? round(($nationalPpm2 / $ppm2) * 100, 1)
                : 0;

            return [
                'city'         => $row->city,
                'listings'     => (int) $row->listings,
                'avg_price'    => (int) $row->avg_price,
                'avg_price_m2' => $ppm2,
                'avg_surface'  => round((float) $row->avg_surface, 1),
                'min_price'    => (int) $row->min_price,
                'max_price'    => (int) $row->max_price,
                'yield_index'  => $yieldIndex,
            ];
        });
    }

    private function priceBuckets(Builder $base): array
    {
        $raw = (clone $base)
            ->selectRaw("
                CASE
                    WHEN ventes.price < 150000 THEN 'under_150'
                    WHEN ventes.price < 250000 THEN '150_250'
                    WHEN ventes.price < 400000 THEN '250_400'
                    WHEN ventes.price < 600000 THEN '400_600'
                    WHEN ventes.price < 1000000 THEN '600_1000'
                    ELSE 'over_1000'
                END as bucket
            ")
            ->selectRaw('COUNT(*) as listings')
            ->groupBy('bucket')
            ->pluck('listings', 'bucket');

        $defs = [
            ['key' => 'under_150', 'label' => '< 150 k€'],
            ['key' => '150_250', 'label' => '150–250 k€'],
            ['key' => '250_400', 'label' => '250–400 k€'],
            ['key' => '400_600', 'label' => '400–600 k€'],
            ['key' => '600_1000', 'label' => '600 k€–1 M€'],
            ['key' => 'over_1000', 'label' => '> 1 M€'],
        ];

        return array_map(fn ($d) => [
            ...$d,
            'listings' => (int) ($raw[$d['key']] ?? 0),
        ], $defs);
    }

    private function priceCurve(Builder $base): array
    {
        $rows = (clone $base)
            ->selectRaw('LEAST((ventes.price / 25000) * 25000, 400000) as bucket')
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->where('ventes.price', '>', 0)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $rows->map(function ($row) {
            $start = (int) $row->bucket;
            $label = $start >= 400000 ? '400k+' : (int) ($start / 1000).'k';

            return [
                'label'    => $label,
                'listings' => (int) $row->listings,
                'avg_price'=> (int) $row->avg_price,
            ];
        })->all();
    }

    private function surfaceBuckets(Builder $base): array
    {
        $raw = (clone $base)
            ->selectRaw("
                CASE
                    WHEN ventes.surface_area < 30 THEN 'under_30'
                    WHEN ventes.surface_area < 50 THEN '30_50'
                    WHEN ventes.surface_area < 70 THEN '50_70'
                    WHEN ventes.surface_area < 100 THEN '70_100'
                    WHEN ventes.surface_area < 150 THEN '100_150'
                    ELSE 'over_150'
                END as bucket
            ")
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->whereNotNull('ventes.surface_area')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $defs = [
            ['key' => 'under_30', 'label' => '< 30 m²'],
            ['key' => '30_50', 'label' => '30–50 m²'],
            ['key' => '50_70', 'label' => '50–70 m²'],
            ['key' => '70_100', 'label' => '70–100 m²'],
            ['key' => '100_150', 'label' => '100–150 m²'],
            ['key' => 'over_150', 'label' => '> 150 m²'],
        ];

        return array_map(fn ($d) => [
            'label'    => $d['label'],
            'listings' => (int) ($raw[$d['key']]->listings ?? 0),
            'avg_price'=> (int) ($raw[$d['key']]->avg_price ?? 0),
        ], $defs);
    }

    private function roomsDistribution(Builder $base): array
    {
        $raw = (clone $base)
            ->selectRaw("CASE WHEN ventes.rooms_quantity >= 6 THEN 6 ELSE ventes.rooms_quantity END as rooms")
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->whereNotNull('ventes.rooms_quantity')
            ->where('ventes.rooms_quantity', '>', 0)
            ->groupBy('rooms')
            ->orderBy('rooms')
            ->get();

        return $raw->map(fn ($row) => [
            'label'    => ((int) $row->rooms) >= 6 ? '6+' : (string) (int) $row->rooms,
            'listings' => (int) $row->listings,
            'avg_price'=> (int) $row->avg_price,
        ])->all();
    }

    private function timeline(Builder $base): array
    {
        $rows = (clone $base)
            ->selectRaw("date_trunc('month', COALESCE(ventes.publication_date, ventes.created_at)) as month")
            ->selectRaw('COUNT(*) as listings')
            ->selectRaw('COALESCE(AVG(ventes.price), 0)::int as avg_price')
            ->selectRaw('COALESCE(AVG(ventes.price_per_sqm), 0)::int as avg_price_m2')
            ->whereRaw("COALESCE(ventes.publication_date, ventes.created_at) >= NOW() - INTERVAL '24 months'")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(function ($row) {
            $month = $row->month ? substr((string) $row->month, 0, 7) : null;

            return [
                'label'        => $month,
                'listings'     => (int) $row->listings,
                'avg_price'    => (int) $row->avg_price,
                'avg_price_m2' => (int) $row->avg_price_m2,
            ];
        })->filter(fn ($r) => $r['label'])->values()->all();
    }
}
