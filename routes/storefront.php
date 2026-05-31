<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Logout as CustomerLogout;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PageShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
|
| Public, customer-facing routes. The `storefront` middleware group resolves
| the current store from the request hostname. Customer account pages add the
| `auth:customer` middleware. Later phases flesh out the cart, checkout, and
| product pages; Phase 3 adds the home page, collections, and CMS pages.
|
*/

Route::middleware('storefront')->group(function () {
    Route::livewire('/', Home::class)->name('storefront.home');

    // Collections + CMS pages.
    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::livewire('/pages/{handle}', PageShow::class)->name('storefront.pages.show');

    // Customer authentication (guest-only).
    Route::middleware('guest:customer')->group(function () {
        Route::livewire('/account/login', CustomerLogin::class)->name('account.login');
        Route::livewire('/account/register', CustomerRegister::class)->name('account.register');
    });

    Route::post('/account/logout', CustomerLogout::class)->name('account.logout');

    // Authenticated customer account pages.
    Route::middleware('auth.customer')->group(function () {
        Route::view('/account', 'storefront.account.dashboard')->name('account.dashboard');
    });
});
