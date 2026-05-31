<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| REST API endpoints. The admin API (Phase 8) is token-authenticated via
| Sanctum and rate limited by `api.admin`; the storefront API (cart, search) is
| public and rate limited by `api.storefront`. Phase 1 only reserves the
| structure and applies the rate limiters; concrete endpoints arrive later.
|
*/

Route::prefix('api')->group(function () {
    Route::middleware('throttle:api.admin')
        ->prefix('admin/v1')
        ->group(function () {
            // Admin REST endpoints (auth:sanctum + store.resolve) — added in Phase 8.
        });

    Route::middleware(['throttle:api.storefront', 'store.resolve'])
        ->prefix('storefront/v1')
        ->group(function () {
            // Public storefront endpoints (cart, search) — added in later phases.
        });
});
