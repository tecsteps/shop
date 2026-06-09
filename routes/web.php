<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Storefront\Auth\CustomerLoginController;
use App\Http\Controllers\Storefront\Auth\CustomerRegisterController;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (no store resolution)
|--------------------------------------------------------------------------
*/

Route::livewire('/admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('/admin/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('admin.login.attempt');

Route::post('/admin/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (session-based store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/admin', fn () => view('admin.dashboard'))->name('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| Storefront Routes (hostname-based store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware('storefront')->group(function (): void {
    Route::get('/', fn () => view('storefront.home'))->name('home');

    Route::livewire('/account/login', CustomerLogin::class)->name('storefront.account.login');
    Route::post('/account/login', [CustomerLoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('storefront.account.login.attempt');

    Route::livewire('/account/register', CustomerRegister::class)->name('storefront.account.register');
    Route::post('/account/register', [CustomerRegisterController::class, 'store'])
        ->name('storefront.account.register.attempt');

    Route::post('/account/logout', [CustomerLoginController::class, 'destroy'])
        ->name('storefront.account.logout');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/account', fn () => view('storefront.account.index'))->name('storefront.account.index');
    });
});
