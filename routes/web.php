<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['storefront'])->group(function (): void {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
});

Route::livewire('admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('admin/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('admin.login');
})->middleware('auth')->name('admin.logout');

Route::view('admin', 'dashboard')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.dashboard');

Route::middleware(['storefront'])->group(function (): void {
    Route::livewire('account/login', CustomerLogin::class)
        ->middleware('guest:customer')
        ->name('account.login');

    Route::livewire('account/register', CustomerRegister::class)
        ->middleware('guest:customer')
        ->name('account.register');

    Route::view('account', 'storefront.account.dashboard')
        ->middleware('auth:customer')
        ->name('account.dashboard');
});

Route::redirect('dashboard', 'admin')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
