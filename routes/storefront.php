<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Public Routes (with store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'store.resolve'])->group(function (): void {
    // Customer auth routes (no customer auth required)
    Route::livewire('/account/login', 'storefront.account.auth.login')
        ->name('storefront.login');

    Route::livewire('/account/register', 'storefront.account.auth.register')
        ->name('storefront.register');

    Route::post('/account/logout', function () {
        auth()->guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    })->name('storefront.logout');

    // Storefront pages
    Route::livewire('/', 'storefront.home')
        ->name('storefront.home');

    Route::livewire('/collections', 'storefront.collections.index')
        ->name('storefront.collections.index');

    Route::livewire('/collections/{handle}', 'storefront.collections.show')
        ->name('storefront.collections.show');

    Route::livewire('/products/{handle}', 'storefront.products.show')
        ->name('storefront.products.show');

    Route::livewire('/pages/{handle}', 'storefront.pages.show')
        ->name('storefront.pages.show');

    Route::livewire('/cart', 'storefront.cart.show')
        ->name('storefront.cart.show');

    Route::livewire('/search', 'storefront.search.index')
        ->name('storefront.search');
});

/*
|--------------------------------------------------------------------------
| Storefront Customer Account Routes (requires customer auth)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'store.resolve', 'auth.customer'])
    ->prefix('account')
    ->group(function (): void {
        Route::livewire('/', 'storefront.account.dashboard')
            ->name('storefront.account.dashboard');

        Route::livewire('/orders', 'storefront.account.orders.index')
            ->name('storefront.account.orders.index');

        Route::livewire('/orders/{orderNumber}', 'storefront.account.orders.show')
            ->name('storefront.account.orders.show');

        Route::livewire('/addresses', 'storefront.account.addresses.index')
            ->name('storefront.account.addresses.index');
    });
