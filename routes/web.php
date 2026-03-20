<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as CustomerDashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrderShow;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

// Admin auth routes
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)
        ->middleware('guest')
        ->name('admin.login');

    Route::post('logout', [AdminLogout::class, 'logout'])
        ->middleware('auth')
        ->name('admin.logout');
});

// Admin panel routes (authenticated + store-scoped)
Route::prefix('admin')
    ->middleware(['auth', 'resolve.store:admin'])
    ->group(function () {
        Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');

        Route::get('products', \App\Livewire\Admin\Products\Index::class)->name('admin.products.index');
        Route::get('products/create', \App\Livewire\Admin\Products\Form::class)->name('admin.products.create');
        Route::get('products/{productId}/edit', \App\Livewire\Admin\Products\Form::class)->name('admin.products.edit');

        Route::get('orders', \App\Livewire\Admin\Orders\Index::class)->name('admin.orders.index');
        Route::get('orders/{orderId}', \App\Livewire\Admin\Orders\Show::class)->name('admin.orders.show');

        Route::get('collections', \App\Livewire\Admin\Collections\Index::class)->name('admin.collections.index');

        Route::get('customers', \App\Livewire\Admin\Customers\Index::class)->name('admin.customers.index');
        Route::get('customers/{customerId}', \App\Livewire\Admin\Customers\Show::class)->name('admin.customers.show');

        Route::get('discounts', \App\Livewire\Admin\Discounts\Index::class)->name('admin.discounts.index');

        Route::get('pages', \App\Livewire\Admin\Pages\Index::class)->name('admin.pages.index');
        Route::get('navigation', \App\Livewire\Admin\Navigation\Index::class)->name('admin.navigation.index');
        Route::get('themes', \App\Livewire\Admin\Themes\Index::class)->name('admin.themes.index');

        Route::get('analytics', \App\Livewire\Admin\Analytics\Index::class)->name('admin.analytics.index');
        Route::get('settings', \App\Livewire\Admin\Settings\Index::class)->name('admin.settings.index');
    });

// Storefront routes
Route::middleware('resolve.store:storefront')->group(function () {
    // Public storefront pages
    Route::get('/', \App\Livewire\Storefront\Home::class)->name('storefront.home');
    Route::get('/collections', \App\Livewire\Storefront\Collections\Index::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', \App\Livewire\Storefront\Collections\Show::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', \App\Livewire\Storefront\Products\Show::class)->name('storefront.products.show');
    Route::get('/cart', \App\Livewire\Storefront\Cart\Show::class)->name('storefront.cart');
    Route::get('/search', \App\Livewire\Storefront\Search\Index::class)->name('storefront.search');
    Route::get('/pages/{handle}', \App\Livewire\Storefront\Pages\Show::class)->name('storefront.pages.show');

    // Customer auth routes (no auth required)
    Route::get('account/login', CustomerLogin::class)
        ->middleware('guest:customer')
        ->name('customer.login');

    Route::get('account/register', CustomerRegister::class)
        ->middleware('guest:customer')
        ->name('customer.register');

    // Customer account routes (auth required)
    Route::middleware('auth:customer')->group(function () {
        Route::get('account', CustomerDashboard::class)->name('customer.account');
        Route::get('account/orders', OrdersIndex::class)->name('customer.orders');
        Route::get('account/orders/{orderNumber}', OrderShow::class)->name('customer.orders.show');
        Route::get('account/addresses', AddressesIndex::class)->name('customer.addresses');
        Route::post('account/logout', [CustomerDashboard::class, 'logout'])->name('customer.logout');
    });
});
