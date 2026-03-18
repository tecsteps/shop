<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin auth routes
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)
        ->middleware('guest')
        ->name('admin.login');

    Route::post('logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
    });
});

// Storefront routes
Route::middleware(['storefront'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('account/login', CustomerLogin::class)->name('storefront.login');
    Route::get('account/register', CustomerRegister::class)->name('storefront.register');

    Route::post('account/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.login');
    })->name('storefront.logout');

    Route::middleware(['auth:customer'])->group(function () {
        Route::get('account', function () {
            return view('storefront.account');
        })->name('storefront.account');
    });
});

require __DIR__.'/settings.php';
