<?php

use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\Auth\LoginController;
use App\Http\Controllers\Customer\Auth\RegisterController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('storefront')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/collections', [CollectionController::class, 'index'])->name('storefront.collections.index');
    Route::get('/collections/{handle}', [CollectionController::class, 'show'])->name('storefront.collections.show');

    Route::get('/products/{handle}', [ProductController::class, 'show'])->name('storefront.products.show');
    Route::get('/search', [SearchController::class, 'index'])->name('storefront.search.index');
    Route::get('/pages/{handle}', [PageController::class, 'show'])->name('storefront.pages.show');

    Route::get('/cart', [CartController::class, 'show'])->name('storefront.cart.show');
    Route::post('/cart/lines', [CartController::class, 'add'])->name('storefront.cart.lines.add');
    Route::patch('/cart/lines/{lineId}', [CartController::class, 'update'])->name('storefront.cart.lines.update');
    Route::delete('/cart/lines/{lineId}', [CartController::class, 'remove'])->name('storefront.cart.lines.remove');
    Route::post('/cart/discount', [CartController::class, 'applyDiscount'])->name('storefront.cart.discount.apply');
    Route::delete('/cart/discount', [CartController::class, 'removeDiscount'])->name('storefront.cart.discount.remove');

    Route::post('/checkout', [CheckoutController::class, 'create'])->name('storefront.checkout.create');
    Route::get('/checkout/{checkoutId}', [CheckoutController::class, 'show'])->name('storefront.checkout.show');
    Route::post('/checkout/{checkoutId}/submit', [CheckoutController::class, 'submit'])->name('storefront.checkout.submit');
    Route::get('/checkout/{checkoutId}/confirmation', [CheckoutController::class, 'confirmation'])->name('storefront.checkout.confirmation');

    Route::prefix('account')->name('storefront.account.')->group(function (): void {
        Route::middleware('guest:customer')->group(function (): void {
            Route::get('/login', [LoginController::class, 'show'])->name('login');
            Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
            Route::get('/register', [RegisterController::class, 'show'])->name('register');
            Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
        });

        Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:customer')->name('logout');

        Route::middleware('auth:customer')->group(function (): void {
            Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])
                ->where('orderNumber', '[^/]+')
                ->name('orders.show');

            Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
            Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
            Route::put('/addresses/{addressId}', [AddressController::class, 'update'])->name('addresses.update');
            Route::delete('/addresses/{addressId}', [AddressController::class, 'destroy'])->name('addresses.destroy');
            Route::patch('/addresses/{addressId}/default', [AddressController::class, 'setDefault'])->name('addresses.default');
        });
    });
});
