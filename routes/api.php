<?php

use Illuminate\Support\Facades\Route;

// Storefront API (cart, checkout, search). Endpoints are added in later phases.
Route::middleware(['store.resolve:storefront', 'throttle:api.storefront'])
    ->prefix('storefront/v1')
    ->group(function (): void {
        //
    });

// Admin REST API (Sanctum personal access tokens). Endpoints are added in later phases.
Route::middleware(['auth:sanctum', 'store.resolve:admin', 'throttle:api.admin'])
    ->prefix('admin/v1')
    ->group(function (): void {
        //
    });
