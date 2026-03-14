<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Storefront routes
Route::get('/', \App\Livewire\Storefront\Home::class)->name('storefront.home');
Route::get('/collections', \App\Livewire\Storefront\Collections\Index::class)->name('storefront.collections.index');
Route::get('/collections/{handle}', \App\Livewire\Storefront\Collections\Show::class)->name('storefront.collections.show');
Route::get('/products/{handle}', \App\Livewire\Storefront\Products\Show::class)->name('storefront.products.show');
Route::get('/pages/{handle}', \App\Livewire\Storefront\Pages\Show::class)->name('storefront.pages.show');
Route::get('/search', \App\Livewire\Storefront\Search\Index::class)->name('storefront.search');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin auth routes (no auth required)
Route::prefix('admin')->group(function () {
    Route::get('login', \App\Livewire\Admin\Auth\Login::class)->name('admin.login');
    Route::post('logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');
});

// Admin authenticated routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard-placeholder');
    })->name('admin.dashboard');
});

// Customer auth routes (storefront)
Route::get('account/login', \App\Livewire\Storefront\Account\Auth\Login::class)->name('customer.login');
Route::get('account/register', \App\Livewire\Storefront\Account\Auth\Register::class)->name('customer.register');
Route::post('account/logout', function () {
    Auth::guard('customer')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('customer.login');
})->name('customer.logout');

// Customer authenticated routes (placeholder)
Route::middleware(['auth:customer'])->group(function () {
    Route::get('account', function () {
        return 'My Account';
    })->name('customer.account');
});

require __DIR__.'/settings.php';
