<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Logout as CustomerLogout;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
|
| Public, customer-facing routes. The `storefront` middleware group resolves
| the current store from the request hostname. Customer account pages add the
| `auth:customer` middleware. Later phases flesh out the catalog, cart, and
| checkout; Phase 1 establishes the structure and customer auth.
|
*/

Route::middleware('storefront')->group(function () {
    Route::get('/', function () {
        return view('storefront.home');
    })->name('storefront.home');

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
