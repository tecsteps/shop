<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Fortify default dashboard route (used by Fortify redirects)
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', AdminLogin::class)->name('login');

    Route::middleware(['auth', 'store.resolve:admin'])->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });

        Route::get('dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    });
});

// Storefront account routes
Route::prefix('account')->name('storefront.account.')->group(function () {
    Route::get('login', CustomerLogin::class)->name('login');
    Route::get('register', CustomerRegister::class)->name('register');
});

require __DIR__.'/settings.php';
