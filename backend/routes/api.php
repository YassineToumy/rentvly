<?php

use App\Http\Controllers\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VenteController as AdminVenteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstimationController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public ──
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/ventes', [VenteController::class, 'index']);
    Route::get('/ventes/stats', [\App\Http\Controllers\VenteStatsController::class, 'index']);
    Route::get('/ventes/{id}', [VenteController::class, 'show']);

    Route::post('/predict', [PredictionController::class, 'predict'])->middleware('throttle:20,1');
    Route::post('/rentability', [PredictionController::class, 'rentability'])->middleware('throttle:20,1');

    // ── Protected (requires token) ──
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/estimations', [EstimationController::class, 'index']);
        Route::get('/estimations/by-listing/{listingId}', [EstimationController::class, 'byListing']);
        Route::post('/estimations', [EstimationController::class, 'store']);
        Route::get('/estimations/{estimation}', [EstimationController::class, 'show']);
        Route::patch('/estimations/{estimation}', [EstimationController::class, 'update']);
        Route::delete('/estimations/{estimation}', [EstimationController::class, 'destroy']);

        // ── Admin ──
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('/stats', [AdminStatsController::class, 'index']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::get('/users/{user}', [AdminUserController::class, 'show']);
            Route::patch('/users/{user}', [AdminUserController::class, 'update']);
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

            Route::get('/ventes', [AdminVenteController::class, 'index']);
            Route::post('/ventes', [AdminVenteController::class, 'store']);
            Route::get('/ventes/{id}', [AdminVenteController::class, 'show']);
            Route::patch('/ventes/{id}', [AdminVenteController::class, 'update']);
            Route::delete('/ventes/{id}', [AdminVenteController::class, 'destroy']);
        });
    });
});