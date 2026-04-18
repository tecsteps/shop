<?php

use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrderShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as PageShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', StorefrontHome::class)->name('storefront.home');

Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
Route::get('/collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
Route::get('/products/{handle}', ProductShow::class)->name('storefront.products.show');
Route::get('/cart', CartShow::class)->name('storefront.cart.show');
Route::get('/checkout', CheckoutShow::class)->name('storefront.checkout.show');
Route::get('/checkout/confirmation/{number}', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
Route::get('/search', SearchIndex::class)->name('storefront.search.index');
Route::get('/pages/{handle}', PageShow::class)->name('storefront.pages.show');

Route::prefix('account')->name('account.')->group(function (): void {
    Route::get('/login', CustomerLogin::class)->name('login');
    Route::get('/register', CustomerRegister::class)->name('register');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/', AccountDashboard::class)->name('dashboard');
        Route::get('/orders', AccountOrdersIndex::class)->name('orders.index');
        Route::get('/orders/{number}', AccountOrderShow::class)->name('orders.show');
        Route::get('/addresses', AccountAddresses::class)->name('addresses.index');
        Route::post('/logout', function () {
            auth()->guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('account.login');
        })->name('logout');
    });
});
