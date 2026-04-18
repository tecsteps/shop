<?php

use Illuminate\Support\Facades\Route;

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
    require __DIR__.'/admin.php';
});

Route::middleware('storefront')->group(function (): void {
    require __DIR__.'/storefront.php';
});
