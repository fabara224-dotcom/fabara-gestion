<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
| API de l'application mobile Flutter (préfixe /api, jeton Sanctum).
| Toutes les routes protégées passent par "tenant" : l'application ne reçoit
| jamais que les données de l'entreprise de l'utilisateur connecté.
*/

Route::post('/v1/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('subscribed')->group(function () {
        // Synchronisation hors ligne
        Route::get('/sync/pull', [SyncController::class, 'pull'])->middleware('throttle:sync');
        Route::post('/sync/push', [SyncController::class, 'push'])->middleware('throttle:sync');

        Route::get('/sales', [SaleController::class, 'index']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);

        Route::middleware('role:owner,manager')->group(function () {
            Route::post('/products/{id}/image', [ProductController::class, 'uploadImage']);
            Route::get('/dashboard', DashboardController::class);
        });
    });
});
