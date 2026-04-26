<?php

use App\Http\Controllers\AuthController;
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
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/ventes', [VenteController::class, 'index']);
    Route::get('/ventes/{id}', [VenteController::class, 'show']);

    Route::post('/predict', [PredictionController::class, 'predict']);
    Route::post('/rentability', [PredictionController::class, 'rentability']);

    // ── Protected (requires token) ──
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});