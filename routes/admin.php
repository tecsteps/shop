<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (no store resolution needed)
|--------------------------------------------------------------------------
*/

Route::middleware('web')->prefix('admin')->group(function (): void {
    Route::livewire('/login', 'admin.auth.login')->name('admin.login');
    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');
});

/*
|--------------------------------------------------------------------------
| Admin Authenticated Routes (with store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'store.resolve'])
    ->prefix('admin')
    ->group(function (): void {
        Route::livewire('/', 'admin.dashboard')->name('admin.dashboard');

        // Products
        Route::livewire('/products', 'admin.products.index')->name('admin.products.index');
        Route::livewire('/products/create', 'admin.products.form')->name('admin.products.create');
        Route::livewire('/products/{product}/edit', 'admin.products.form')->name('admin.products.edit');

        // Orders
        Route::livewire('/orders', 'admin.orders.index')->name('admin.orders.index');
        Route::livewire('/orders/{order}', 'admin.orders.show')->name('admin.orders.show');

        // Collections
        Route::livewire('/collections', 'admin.collections.index')->name('admin.collections.index');
        Route::livewire('/collections/create', 'admin.collections.form')->name('admin.collections.create');
        Route::livewire('/collections/{collection}/edit', 'admin.collections.form')->name('admin.collections.edit');

        // Customers
        Route::livewire('/customers', 'admin.customers.index')->name('admin.customers.index');
        Route::livewire('/customers/{customer}', 'admin.customers.show')->name('admin.customers.show');

        // Discounts
        Route::livewire('/discounts', 'admin.discounts.index')->name('admin.discounts.index');
        Route::livewire('/discounts/create', 'admin.discounts.form')->name('admin.discounts.create');
        Route::livewire('/discounts/{discount}/edit', 'admin.discounts.form')->name('admin.discounts.edit');

        // Settings
        Route::livewire('/settings', 'admin.settings.index')->name('admin.settings.index');
        Route::livewire('/settings/shipping', 'admin.settings.shipping')->name('admin.settings.shipping');
        Route::livewire('/settings/taxes', 'admin.settings.taxes')->name('admin.settings.taxes');

        // Content
        Route::livewire('/pages', 'admin.pages.index')->name('admin.pages.index');
        Route::livewire('/pages/create', 'admin.pages.form')->name('admin.pages.create');
        Route::livewire('/pages/{page}/edit', 'admin.pages.form')->name('admin.pages.edit');
        Route::livewire('/navigation', 'admin.navigation.index')->name('admin.navigation.index');

        // Analytics
        Route::livewire('/analytics', 'admin.analytics.index')->name('admin.analytics.index');

        // Inventory
        Route::livewire('/inventory', 'admin.inventory.index')->name('admin.inventory.index');

        // Developers
        Route::livewire('/developers', 'admin.developers.index')->name('admin.developers.index');
    });
