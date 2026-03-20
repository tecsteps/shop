<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('admin')->group(function () {
    Route::get('login', \App\Livewire\Admin\Auth\Login::class)
        ->name('admin.login');

    Route::post('logout', function () {
        auth()->guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::get('/', function () {
        return view('admin.dashboard');
    })->middleware(['auth'])->name('admin.dashboard');
});

Route::prefix('account')->middleware(['storefront'])->group(function () {
    Route::get('login', \App\Livewire\Storefront\Account\Auth\Login::class)
        ->name('storefront.login');

    Route::get('register', \App\Livewire\Storefront\Account\Auth\Register::class)
        ->name('storefront.register');

    Route::get('/', function () {
        return view('storefront.account.dashboard');
    })->middleware(['auth.customer'])->name('storefront.account');

    Route::post('logout', function () {
        auth()->guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('storefront.login');
    })->middleware(['auth.customer'])->name('storefront.logout');
});

require __DIR__.'/settings.php';
