<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Home as StorefrontHome;
use Illuminate\Support\Facades\Route;

Route::get('/', StorefrontHome::class)->name('storefront.home');

Route::prefix('account')->name('account.')->group(function (): void {
    Route::get('/login', CustomerLogin::class)->name('login');
    Route::get('/register', CustomerRegister::class)->name('register');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/', AccountDashboard::class)->name('dashboard');
        Route::post('/logout', function () {
            auth()->guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('account.login');
        })->name('logout');
    });
});
