<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\StoreSelectorController;
use App\Http\Controllers\Storefront\Auth\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Storefront\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Storefront\Auth\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Storefront\Auth\ResetPasswordController as CustomerResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home')->middleware('store.resolve');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');
    });

    Route::post('logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')->group(function (): void {
        Route::get('stores/select', [StoreSelectorController::class, 'index'])->name('store-selector');
        Route::post('stores/select', [StoreSelectorController::class, 'store'])->name('store-selector.store');
    });

    Route::view('/', 'admin.dashboard')
        ->middleware(['auth', 'verified', 'store.resolve', 'role.check:owner,admin,staff,support'])
        ->name('dashboard');
});

Route::middleware('store.resolve')->group(function (): void {
    Route::middleware('guest:customer')->group(function (): void {
        Route::get('account/login', [CustomerLoginController::class, 'create'])->name('customer.login');
        Route::post('account/login', [CustomerLoginController::class, 'store'])
            ->middleware('throttle:login')
            ->name('customer.login.store');
        Route::get('account/register', [CustomerRegisterController::class, 'create'])->name('customer.register');
        Route::post('account/register', [CustomerRegisterController::class, 'store'])->name('customer.register.store');
        Route::get('account/forgot-password', [CustomerForgotPasswordController::class, 'create'])->name('customer.password.request');
        Route::post('account/forgot-password', [CustomerForgotPasswordController::class, 'store'])->name('customer.password.email');
        Route::get('account/reset-password/{token}', [CustomerResetPasswordController::class, 'create'])->name('customer.password.reset');
        Route::post('account/reset-password', [CustomerResetPasswordController::class, 'store'])->name('customer.password.update');
    });

    Route::post('account/logout', [CustomerLoginController::class, 'destroy'])->name('customer.logout');

    Route::view('account', 'storefront.account.dashboard')
        ->middleware('customer.auth')
        ->name('customer.account');
});

require __DIR__.'/settings.php';
