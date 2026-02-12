<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Admin Auth Routes (no auth required)
Route::prefix('admin')->group(function () {
    Route::get('/login', \App\Livewire\Admin\Auth\Login::class)->name('admin.login');
    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');
});

// Admin Routes (auth required)
Route::prefix('admin')
    ->middleware(['auth', 'store.resolve:admin'])
    ->group(function () {
        Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');

        // Products
        Route::get('/products', \App\Livewire\Admin\Products\Index::class)->name('admin.products.index');
        Route::get('/products/create', \App\Livewire\Admin\Products\Create::class)->name('admin.products.create');
        Route::get('/products/{product}/edit', \App\Livewire\Admin\Products\Edit::class)->name('admin.products.edit');

        // Inventory
        Route::get('/inventory', \App\Livewire\Admin\Inventory\Index::class)->name('admin.inventory.index');

        // Collections
        Route::get('/collections', \App\Livewire\Admin\Collections\Index::class)->name('admin.collections.index');
        Route::get('/collections/create', \App\Livewire\Admin\Collections\Create::class)->name('admin.collections.create');
        Route::get('/collections/{collection}/edit', \App\Livewire\Admin\Collections\Edit::class)->name('admin.collections.edit');

        // Orders
        Route::get('/orders', \App\Livewire\Admin\Orders\Index::class)->name('admin.orders.index');
        Route::get('/orders/{order}', \App\Livewire\Admin\Orders\Show::class)->name('admin.orders.show');

        // Customers
        Route::get('/customers', \App\Livewire\Admin\Customers\Index::class)->name('admin.customers.index');
        Route::get('/customers/{customer}', \App\Livewire\Admin\Customers\Show::class)->name('admin.customers.show');

        // Discounts
        Route::get('/discounts', \App\Livewire\Admin\Discounts\Index::class)->name('admin.discounts.index');
        Route::get('/discounts/create', \App\Livewire\Admin\Discounts\Create::class)->name('admin.discounts.create');
        Route::get('/discounts/{discount}/edit', \App\Livewire\Admin\Discounts\Edit::class)->name('admin.discounts.edit');

        // Settings
        Route::get('/settings', \App\Livewire\Admin\Settings\General::class)->name('admin.settings.index');
        Route::get('/settings/shipping', \App\Livewire\Admin\Settings\Shipping::class)->name('admin.settings.shipping');
        Route::get('/settings/taxes', \App\Livewire\Admin\Settings\Taxes::class)->name('admin.settings.taxes');

        // Themes
        Route::get('/themes', \App\Livewire\Admin\Themes\Index::class)->name('admin.themes.index');
        Route::get('/themes/{theme}/editor', \App\Livewire\Admin\Themes\Editor::class)->name('admin.themes.editor');

        // Pages
        Route::get('/pages', \App\Livewire\Admin\Pages\Index::class)->name('admin.pages.index');
        Route::get('/pages/create', \App\Livewire\Admin\Pages\Create::class)->name('admin.pages.create');
        Route::get('/pages/{page}/edit', \App\Livewire\Admin\Pages\Edit::class)->name('admin.pages.edit');

        // Navigation
        Route::get('/navigation', \App\Livewire\Admin\Navigation\Index::class)->name('admin.navigation.index');

        // Analytics
        Route::get('/analytics', \App\Livewire\Admin\Analytics\Index::class)->name('admin.analytics.index');

        // Search Settings
        Route::get('/search/settings', \App\Livewire\Admin\Search\Settings::class)->name('admin.search.settings');

        // Apps
        Route::get('/apps', \App\Livewire\Admin\Apps\Index::class)->name('admin.apps.index');
        Route::get('/apps/{installation}', \App\Livewire\Admin\Apps\Show::class)->name('admin.apps.show');

        // Developers
        Route::get('/developers', \App\Livewire\Admin\Developers\Index::class)->name('admin.developers.index');
    });

// Storefront Routes
Route::middleware(['store.resolve:storefront'])->group(function () {
    Route::get('/', \App\Livewire\Storefront\Home::class)->name('storefront.home');

    Route::get('/collections', \App\Livewire\Storefront\Collections\Index::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', \App\Livewire\Storefront\Collections\Show::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', \App\Livewire\Storefront\Products\Show::class)->name('storefront.products.show');
    Route::get('/cart', \App\Livewire\Storefront\Cart\Show::class)->name('storefront.cart.show');
    Route::get('/search', \App\Livewire\Storefront\Search\Index::class)->name('storefront.search');
    Route::get('/pages/{handle}', \App\Livewire\Storefront\Pages\Show::class)->name('storefront.pages.show');

    // Customer Auth Routes
    Route::get('/account/login', \App\Livewire\Storefront\Account\Auth\Login::class)->name('storefront.account.login');
    Route::get('/account/register', \App\Livewire\Storefront\Account\Auth\Register::class)->name('storefront.account.register');

    // Checkout Routes
    Route::get('/checkout/{checkoutId}', \App\Livewire\Storefront\Checkout\Show::class)->name('storefront.checkout.show');
    Route::get('/checkout/{checkoutId}/confirmation', \App\Livewire\Storefront\Checkout\Confirmation::class)->name('storefront.checkout.confirmation');

    // Customer Account Routes (auth required)
    Route::middleware(['auth.customer'])->prefix('account')->group(function () {
        Route::get('/', \App\Livewire\Storefront\Account\Dashboard::class)->name('storefront.account.dashboard');
        Route::get('/orders', \App\Livewire\Storefront\Account\Orders\Index::class)->name('storefront.account.orders');
        Route::get('/orders/{orderNumber}', \App\Livewire\Storefront\Account\Orders\Show::class)->name('storefront.account.orders.show');
        Route::get('/addresses', \App\Livewire\Storefront\Account\Addresses\Index::class)->name('storefront.account.addresses');
        Route::post('/logout', function () {
            Auth::guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('storefront.account.login');
        })->name('storefront.account.logout');
    });
});
