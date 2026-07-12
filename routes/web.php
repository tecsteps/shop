<?php

use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as CustomerForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as CustomerResetPassword;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrders;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrder;
use App\Livewire\Storefront\Cart\Show as Cart;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show as Checkout;
use App\Livewire\Storefront\Collections\Index as Collections;
use App\Livewire\Storefront\Collections\Show as Collection;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as Page;
use App\Livewire\Storefront\Products\Show as Product;
use App\Livewire\Storefront\Search\Index as Search;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/oauth/authorize', fn () => response()->json([
    'message' => 'The OAuth app ecosystem is not implemented in this release.',
], 501));
Route::post('/oauth/token', fn () => response()->json([
    'message' => 'The OAuth app ecosystem is not implemented in this release.',
], 501))->withoutMiddleware(ValidateCsrfToken::class);

Route::middleware('storefront')->group(function (): void {
    Route::get('/', Home::class)->name('storefront.home');
    Route::get('/collections', Collections::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', Collection::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', Product::class)->name('storefront.products.show');
    Route::get('/cart', Cart::class)->name('storefront.cart');
    Route::get('/search', Search::class)->name('storefront.search');
    Route::get('/pages/{handle}', Page::class)->name('storefront.pages.show');

    Route::get('/account/login', CustomerLogin::class)->name('customer.login');
    Route::get('/account/register', CustomerRegister::class)->name('customer.register');
    Route::get('/forgot-password', CustomerForgotPassword::class)->name('customer.password.request');
    Route::get('/reset-password/{token}', CustomerResetPassword::class)->name('customer.password.reset');

    Route::middleware('customer.auth')->group(function (): void {
        Route::get('/account', AccountDashboard::class)->name('customer.dashboard');
        Route::get('/account/orders', AccountOrders::class)->name('customer.orders.index');
        Route::get('/account/orders/{orderNumber}', AccountOrder::class)->name('customer.orders.show');
        Route::get('/account/addresses', AccountAddresses::class)->name('customer.addresses');
        Route::post('/account/logout', function () {
            Auth::guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/account/login');
        })->name('customer.logout');
    });

    Route::get('/checkout/{checkoutId}', Checkout::class)->middleware('throttle:checkout')->name('storefront.checkout');
    Route::get('/checkout/{checkoutId}/confirmation', Confirmation::class)->name('storefront.checkout.confirmation');
});

Route::redirect('/home', '/')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
