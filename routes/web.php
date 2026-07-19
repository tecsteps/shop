<?php

use Illuminate\Support\Facades\Route;

// Storefront routes (Phase 3 replaces the placeholder with real pages).
Route::middleware(['store.resolve:storefront'])->group(function (): void {
    Route::get('/', fn (): string => 'storefront ok');
});
