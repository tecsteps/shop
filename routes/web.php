<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy compatibility route
|--------------------------------------------------------------------------
*/

Route::redirect('dashboard', '/admin')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)->name('admin.login');

    Route::middleware(['auth:web'])->group(function () {
        Route::post('logout', [AdminLogout::class, 'logout'])->name('admin.logout');

        Route::middleware(['resolve.store:admin'])->group(function () {
            Route::get('/', function () {
                return view('admin.dashboard');
            })->name('admin.dashboard');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';

Route::middleware(['resolve.store:storefront'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('account/login', CustomerLogin::class)->name('customer.login');
    Route::get('account/register', CustomerRegister::class)->name('customer.register');

    Route::middleware(['auth:customer'])->prefix('account')->group(function () {
        Route::post('logout', function () {
            auth()->guard('customer')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('customer.login');
        })->name('customer.logout');

        Route::get('/', function () {
            return view('storefront.account.dashboard');
        })->name('customer.dashboard');
    });
});
