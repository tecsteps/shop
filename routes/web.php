<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Storefront\Auth\CustomerLoginController;
use App\Http\Controllers\Storefront\Auth\CustomerRegisterController;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (no store resolution)
|--------------------------------------------------------------------------
*/

Route::livewire('/admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('/admin/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('admin.login.attempt');

Route::post('/admin/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (session-based store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/admin', fn () => view('admin.dashboard'))->name('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| Storefront Routes (hostname-based store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware('storefront')->group(function (): void {
    Route::livewire('/', Home::class)->name('home');

    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::livewire('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');

    Route::livewire('/cart', CartShow::class)->name('storefront.cart');
    Route::livewire('/checkout', CheckoutShow::class)->name('storefront.checkout');
    Route::livewire('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.confirmation');

    Route::livewire('/account/login', CustomerLogin::class)->name('storefront.account.login');
    Route::post('/account/login', [CustomerLoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('storefront.account.login.attempt');

    Route::livewire('/account/register', CustomerRegister::class)->name('storefront.account.register');
    Route::post('/account/register', [CustomerRegisterController::class, 'store'])
        ->name('storefront.account.register.attempt');

    Route::post('/account/logout', [CustomerLoginController::class, 'destroy'])
        ->name('storefront.account.logout');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/account', fn () => view('storefront.account.index'))->name('storefront.account.index');
    });
});
