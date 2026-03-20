<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

// Admin auth routes
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)
        ->middleware('guest')
        ->name('admin.login');

    Route::post('logout', [AdminLogout::class, 'logout'])
        ->middleware('auth')
        ->name('admin.logout');
});

// Customer storefront auth routes
Route::middleware('resolve.store:storefront')->group(function () {
    Route::get('account/login', CustomerLogin::class)
        ->middleware('guest:customer')
        ->name('customer.login');

    Route::get('account/register', CustomerRegister::class)
        ->middleware('guest:customer')
        ->name('customer.register');
});
