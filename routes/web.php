<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Storefront\Account\Auth\Login as StorefrontLogin;
use App\Livewire\Storefront\Account\Auth\Register as StorefrontRegister;
use App\Livewire\Storefront\Cart\Show as StorefrontCartShow;
use App\Livewire\Storefront\Checkout\Show as StorefrontCheckoutShow;
use App\Livewire\Storefront\Checkout\Success as StorefrontCheckoutSuccess;
use App\Livewire\Storefront\Collections\Show as StorefrontCollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as StorefrontPageShow;
use App\Livewire\Storefront\Products\Show as StorefrontProductShow;
use App\Livewire\Storefront\Search\Index as StorefrontSearch;
use Illuminate\Support\Facades\Route;

Route::view('welcome', 'welcome')->name('welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::livewire('login', AdminLogin::class)->name('login');
    });

    Route::middleware(['auth', 'verified', 'store.resolve:admin'])->group(function (): void {
        Route::view('/', 'admin.dashboard')->name('dashboard');
        Route::livewire('orders', AdminOrdersIndex::class)->name('orders.index');
        Route::livewire('orders/{order}', AdminOrdersShow::class)->name('orders.show');
    });
});

Route::middleware('store.resolve:storefront')->group(function (): void {
    Route::prefix('account')->name('account.')->group(function (): void {
        Route::livewire('login', StorefrontLogin::class)->name('login');
        Route::livewire('register', StorefrontRegister::class)->name('register');
    });

    Route::livewire('/', StorefrontHome::class)->name('home');
    Route::livewire('/search', StorefrontSearch::class)->name('storefront.search');
    Route::livewire('/collections/{handle}', StorefrontCollectionShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', StorefrontProductShow::class)->name('storefront.products.show');
    Route::livewire('/pages/{handle}', StorefrontPageShow::class)->name('storefront.pages.show');

    Route::livewire('/cart', StorefrontCartShow::class)->name('storefront.cart.show');
    Route::livewire('/checkout', StorefrontCheckoutShow::class)->name('storefront.checkout.show');
    Route::livewire('/checkout/success', StorefrontCheckoutSuccess::class)->name('storefront.checkout.success');
});
