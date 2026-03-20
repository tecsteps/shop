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
