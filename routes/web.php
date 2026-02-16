<?php

use App\Livewire\Admin;
use App\Livewire\Storefront;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function (): void {
    Route::get('login', Admin\Auth\Login::class)->name('admin.login');

    Route::middleware(['auth', 'store.resolve'])->group(function (): void {
        Route::get('/', Admin\Dashboard::class)->name('admin.dashboard');
    });
});

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/

Route::middleware('store.resolve')->group(function (): void {
    Route::get('/', Storefront\Home::class)->name('storefront.home');
    Route::get('/account/login', Storefront\Account\Auth\Login::class)->name('storefront.login');
    Route::get('/account/register', Storefront\Account\Auth\Register::class)->name('storefront.register');
});

require __DIR__.'/settings.php';
