<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estimation;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'users_total'        => User::count(),
                'users_admins'       => User::where('role', 'admin')->count(),
                'users_investors'    => User::where('role', 'investor')->count(),
                'ventes_total'       => Vente::count(),
                'estimations_total'  => Estimation::count(),
                'purchased_total'    => Estimation::where('is_purchased', true)->count(),
                'ventes_avg_price'   => (int) round(Vente::avg('price') ?? 0),
                'recent_users'       => User::latest()->limit(5)->get(['id', 'name', 'email', 'role', 'created_at']),
                'recent_ventes'      => Vente::latest('publication_date')->limit(5)
                    ->get(['id', 'title', 'city', 'price', 'property_type', 'publication_date']),
            ],
        ]);
    }
}
