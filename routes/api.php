<?php

use Illuminate\Support\Facades\Route;

// Admin API
Route::prefix('admin/v1')
    ->middleware(['auth:sanctum', 'throttle:api.admin'])
    ->group(function () {
        // Placeholder for admin API routes
    });

// Storefront API
Route::prefix('storefront/v1')
    ->middleware(['storefront', 'throttle:api.storefront'])
    ->group(function () {
        // Placeholder for storefront API routes (cart, checkout, search)
    });
