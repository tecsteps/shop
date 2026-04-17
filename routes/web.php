<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as StorefrontLogin;
use App\Livewire\Storefront\Account\Auth\Register as StorefrontRegister;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

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
    });
});

Route::middleware('store.resolve:storefront')->group(function (): void {
    Route::prefix('account')->name('account.')->group(function (): void {
        Route::livewire('login', StorefrontLogin::class)->name('login');
        Route::livewire('register', StorefrontRegister::class)->name('register');
    });
});
