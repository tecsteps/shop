<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressBook;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Logout as CustomerLogout;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrderShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PageShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
|
| Public, customer-facing routes. The `storefront` middleware group resolves
| the current store from the request hostname. Customer account pages add the
| `auth.customer` middleware. Phase 3 adds home/collections/CMS pages; task #6
| adds product, cart, checkout, search, and the customer account area.
|
*/

Route::middleware('storefront')->group(function () {
    Route::livewire('/', Home::class)->name('storefront.home');

    // Catalog browsing.
    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', ProductShow::class)->name('storefront.products.show');
    Route::livewire('/pages/{handle}', PageShow::class)->name('storefront.pages.show');
    Route::livewire('/search', SearchIndex::class)->name('storefront.search');

    // Cart + checkout. Checkout resolves the active checkout from the session
    // cart; confirmation binds the order by id (segment is {orderId} to avoid a
    // collision with the component's public Order $order property).
    Route::livewire('/cart', CartShow::class)->name('storefront.cart');
    Route::livewire('/checkout', CheckoutShow::class)->name('storefront.checkout');
    Route::livewire('/checkout/confirmation/{orderId}', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');

    // Customer authentication (guest-only).
    Route::middleware('guest:customer')->group(function () {
        Route::livewire('/account/login', CustomerLogin::class)->name('account.login');
        Route::livewire('/account/register', CustomerRegister::class)->name('account.register');
    });

    Route::post('/account/logout', CustomerLogout::class)->name('account.logout');

    // Authenticated customer account pages.
    Route::middleware('auth.customer')->group(function () {
        Route::livewire('/account', AccountDashboard::class)->name('account.dashboard');
        Route::livewire('/account/orders', OrdersIndex::class)->name('account.orders.index');
        Route::livewire('/account/orders/{orderNumber}', OrderShow::class)->name('account.orders.show');
        Route::livewire('/account/addresses', AddressBook::class)->name('account.addresses');
    });
});
