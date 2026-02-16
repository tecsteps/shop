<?php

use Illuminate\Support\Facades\Route;

Route::middleware('store.resolve')->prefix('storefront')->group(function (): void {
    // Storefront API routes
});

Route::middleware(['auth:sanctum', 'store.resolve'])->prefix('admin')->group(function (): void {
    // Admin API routes
});
