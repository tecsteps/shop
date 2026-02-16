<?php

use App\Livewire\Admin;
use App\Livewire\Storefront;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function (): void {
    Route::get('login', Admin\Auth\Login::class)->name('admin.login');

    Route::middleware(['auth', 'store.resolve'])->group(function (): void {
        Route::get('/', Admin\Dashboard::class)->name('admin.dashboard');

        // Products
        Route::get('/products', Admin\Products\Index::class)->name('admin.products.index');
        Route::get('/products/create', Admin\Products\Form::class)->name('admin.products.create');
        Route::get('/products/{product}/edit', Admin\Products\Form::class)->name('admin.products.edit');

        // Orders
        Route::get('/orders', Admin\Orders\Index::class)->name('admin.orders.index');
        Route::get('/orders/{order}', Admin\Orders\Show::class)->name('admin.orders.show');

        // Collections
        Route::get('/collections', Admin\Collections\Index::class)->name('admin.collections.index');
        Route::get('/collections/create', Admin\Collections\Form::class)->name('admin.collections.create');
        Route::get('/collections/{collection}/edit', Admin\Collections\Form::class)->name('admin.collections.edit');

        // Customers
        Route::get('/customers', Admin\Customers\Index::class)->name('admin.customers.index');
        Route::get('/customers/{customer}', Admin\Customers\Show::class)->name('admin.customers.show');

        // Discounts
        Route::get('/discounts', Admin\Discounts\Index::class)->name('admin.discounts.index');
        Route::get('/discounts/create', Admin\Discounts\Form::class)->name('admin.discounts.create');
        Route::get('/discounts/{discount}/edit', Admin\Discounts\Form::class)->name('admin.discounts.edit');

        // Settings
        Route::get('/settings', Admin\Settings\Index::class)->name('admin.settings.index');
        Route::get('/settings/shipping', Admin\Settings\Shipping::class)->name('admin.settings.shipping');
        Route::get('/settings/taxes', Admin\Settings\Taxes::class)->name('admin.settings.taxes');

        // Inventory
        Route::get('/inventory', Admin\Inventory\Index::class)->name('admin.inventory.index');

        // Themes
        Route::get('/themes', Admin\Themes\Index::class)->name('admin.themes.index');

        // Pages
        Route::get('/pages', Admin\Pages\Index::class)->name('admin.pages.index');
        Route::get('/pages/create', Admin\Pages\Form::class)->name('admin.pages.create');
        Route::get('/pages/{page}/edit', Admin\Pages\Form::class)->name('admin.pages.edit');

        // Navigation
        Route::get('/navigation', Admin\Navigation\Index::class)->name('admin.navigation.index');

        // Analytics
        Route::get('/analytics', Admin\Analytics\Index::class)->name('admin.analytics.index');

        // Developers
        Route::get('/developers', Admin\Developers\Index::class)->name('admin.developers.index');
    });
});

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/

Route::middleware('store.resolve')->group(function (): void {
    Route::get('/', Storefront\Home::class)->name('storefront.home');
    Route::get('/collections', Storefront\Collections\Index::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', Storefront\Collections\Show::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', Storefront\Products\Show::class)->name('storefront.products.show');
    Route::get('/cart', Storefront\Cart\Show::class)->name('storefront.cart');
    Route::get('/search', Storefront\Search\Index::class)->name('storefront.search');
    Route::get('/pages/{handle}', Storefront\Pages\Show::class)->name('storefront.pages.show');
    Route::get('/checkout', Storefront\Checkout\Show::class)->name('storefront.checkout');
    Route::get('/checkout/confirmation', Storefront\Checkout\Confirmation::class)->name('storefront.checkout.confirmation');
    Route::get('/account/login', Storefront\Account\Auth\Login::class)->name('storefront.login');
    Route::get('/account/register', Storefront\Account\Auth\Register::class)->name('storefront.register');

    // Customer account (authenticated)
    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/account', Storefront\Account\Dashboard::class)->name('storefront.account');
        Route::get('/account/orders', Storefront\Account\Orders\Index::class)->name('storefront.account.orders');
        Route::get('/account/orders/{orderNumber}', Storefront\Account\Orders\Show::class)->name('storefront.account.orders.show');
        Route::get('/account/addresses', Storefront\Account\Addresses\Index::class)->name('storefront.account.addresses');

        Route::post('/account/logout', function () {
            Auth::guard('customer')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect('/account/login');
        })->name('storefront.account.logout');
    });
});

require __DIR__.'/settings.php';
