<?php

use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

Route::middleware('storefront')->group(function (): void {
    require __DIR__.'/storefront.php';
});
