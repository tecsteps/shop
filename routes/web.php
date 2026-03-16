<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin Auth Routes (no store resolution needed)
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)->name('admin.login');
    Route::post('logout', [AdminLogout::class, 'logout'])->name('admin.logout');
});

// Admin Routes (authenticated, store resolved from session)
Route::prefix('admin')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function () {
        Route::get('/', function () {
            return view('dashboard');
        })->name('admin.dashboard');
    });

// Storefront Routes (store resolved from hostname)
Route::middleware(['storefront'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('account/login', CustomerLogin::class)->name('storefront.account.login');
    Route::get('account/register', CustomerRegister::class)->name('storefront.account.register');

    // Authenticated Customer Routes
    Route::middleware(['auth:customer'])->group(function () {
        Route::get('account', function () {
            return view('storefront.account.dashboard');
        })->name('storefront.account.dashboard');
    });
});

require __DIR__.'/settings.php';
