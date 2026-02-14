<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.auth.')
    ->group(function (): void {
        Route::get('login', fn (): string => 'Admin login placeholder')->name('login');
        Route::get('forgot-password', fn (): string => 'Admin forgot-password placeholder')->name('forgot-password');
        Route::get('reset-password/{token}', fn (): string => 'Admin reset-password placeholder')->name('reset-password');

        Route::post('logout', function (Request $request): RedirectResponse {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.auth.login');
        })->middleware('auth')->name('logout');
    });

Route::middleware(['auth', 'verified', 'store.resolve', 'role.check'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn (): string => 'Admin dashboard placeholder')->name('dashboard');
    });

Route::middleware('store.resolve')->group(function (): void {
    Route::get('/', fn () => view('welcome'))->name('home');
    Route::get('/collections', fn (): string => 'Storefront collections placeholder')->name('storefront.collections.index');
    Route::get('/collections/{handle}', fn (string $handle): string => sprintf('Storefront collection placeholder: %s', $handle))->name('storefront.collections.show');
    Route::get('/products/{handle}', fn (string $handle): string => sprintf('Storefront product placeholder: %s', $handle))->name('storefront.products.show');
    Route::get('/cart', fn (): string => 'Storefront cart placeholder')->name('storefront.cart.show');
    Route::get('/search', fn (): string => 'Storefront search placeholder')->name('storefront.search.index');
    Route::get('/pages/{handle}', fn (string $handle): string => sprintf('Storefront page placeholder: %s', $handle))->name('storefront.pages.show');
    Route::get('/checkout/{checkoutId}', fn (string $checkoutId): string => sprintf('Checkout placeholder: %s', $checkoutId))->name('storefront.checkout.show');
    Route::get('/checkout/{checkoutId}/confirmation', fn (string $checkoutId): string => sprintf('Checkout confirmation placeholder: %s', $checkoutId))->name('storefront.checkout.confirmation');

    Route::prefix('account')
        ->name('account.')
        ->group(function (): void {
            Route::get('login', fn (): string => 'Customer login placeholder')->name('login');
            Route::get('register', fn (): string => 'Customer register placeholder')->name('register');
            Route::get('forgot-password', fn (): string => 'Customer forgot-password placeholder')->name('forgot-password');
            Route::get('reset-password/{token}', fn (): string => 'Customer reset-password placeholder')->name('reset-password');

            Route::middleware('auth:customer')->group(function (): void {
                Route::get('/', fn (): string => 'Customer account dashboard placeholder')->name('dashboard');
                Route::get('/orders', fn (): string => 'Customer account orders placeholder')->name('orders.index');
                Route::get('/orders/{orderNumber}', fn (string $orderNumber): string => sprintf('Customer order placeholder: %s', $orderNumber))->name('orders.show');
                Route::get('/addresses', fn (): string => 'Customer account addresses placeholder')->name('addresses.index');
            });
        });
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
