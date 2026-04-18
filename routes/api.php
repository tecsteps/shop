<?php

use Illuminate\Support\Facades\Route;

Route::middleware('throttle:storefront-api')->prefix('storefront/v1')->name('api.storefront.')->group(function (): void {
    require __DIR__.'/api/storefront.php';
});

Route::middleware(['auth:sanctum', 'throttle:admin-api'])->prefix('admin/v1')->name('api.admin.')->group(function (): void {
    require __DIR__.'/api/admin.php';
});
