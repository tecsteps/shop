<?php

use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/home', '/')->name('home');

Route::middleware('storefront')->group(function (): void {
    Route::get('/', Home::class)->name('storefront.home');
    Route::get('/collections', Collections::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', Collection::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', Product::class)->name('storefront.products.show');
    Route::get('/cart', Cart::class)->name('storefront.cart.show');
    Route::get('/search', Search::class)->name('storefront.search');
    Route::get('/pages/{handle}', Page::class)->name('storefront.pages.show');
    Route::get('/checkout/{checkoutId}', Checkout::class)->name('storefront.checkout.show');
    Route::get('/checkout/{checkoutId}/confirmation', Confirmation::class)->name('storefront.checkout.confirmation');

    Route::middleware('guest:customer')->group(function (): void {
        Route::get('/account/login', CustomerLogin::class)->name('storefront.account.login');
        Route::get('/account/register', CustomerRegister::class)->name('storefront.account.register');
    });

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/account', AccountDashboard::class)->name('storefront.account.dashboard');
        Route::get('/account/orders', AccountOrders::class)->name('storefront.account.orders.index');
        Route::get('/account/orders/{orderNumber}', AccountOrder::class)->name('storefront.account.orders.show');
        Route::get('/account/addresses', AccountAddresses::class)->name('storefront.account.addresses.index');
        Route::post('/account/logout', function (Request $request) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('storefront.account.login');
        })->name('storefront.account.logout');
    });
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
