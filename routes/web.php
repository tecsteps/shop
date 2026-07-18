<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('storefront')->group(function () {
    Route::get('/storefront-ping', function () {
        return 'store:'.app('current_store')->id;
    })->name('storefront.ping');
});

Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/store-ping', function () {
        return 'store:'.app('current_store')->id;
    })->name('admin.store.ping');
});

require __DIR__.'/settings.php';
