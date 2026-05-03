<?php

use App\Livewire\Storefront\Account\Addresses\Index as AccountAddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login as AccountLogin;
use App\Livewire\Storefront\Account\Auth\Register as AccountRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrdersShow;
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

Route::middleware('storefront')->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', ProductShow::class)->name('storefront.products.show');
    Route::get('/cart', CartShow::class)->name('storefront.cart.show');
    Route::get('/checkout/{checkoutId}', CheckoutShow::class)->name('storefront.checkout.show');
    Route::get('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
    Route::get('/search', SearchIndex::class)->name('storefront.search.index');
    Route::get('/pages/{handle}', PageShow::class)->name('storefront.pages.show');

    Route::get('/account/login', AccountLogin::class)->name('storefront.account.login');
    Route::get('/account/register', AccountRegister::class)->name('storefront.account.register');

    Route::middleware('customer.auth')->group(function (): void {
        Route::get('/account', AccountDashboard::class)->name('storefront.account.dashboard');
        Route::get('/account/orders', AccountOrdersIndex::class)->name('storefront.account.orders.index');
        Route::get('/account/orders/{orderNumber}', AccountOrdersShow::class)->name('storefront.account.orders.show');
        Route::get('/account/addresses', AccountAddressesIndex::class)->name('storefront.account.addresses.index');
    });
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
