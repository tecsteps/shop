<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Home;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/

// Public storefront routes
Route::middleware(['store.resolve'])->group(function () {
    Route::get('/', Home::class)->name('storefront.home');

    // Customer auth routes
    Route::get('/account/login', CustomerLogin::class)->name('customer.login');
    Route::get('/account/register', CustomerRegister::class)->name('customer.register');

    // Customer protected routes
    Route::middleware(['auth:customer'])->prefix('account')->group(function () {
        Route::get('/', AccountDashboard::class)->name('customer.dashboard');
    });
});
