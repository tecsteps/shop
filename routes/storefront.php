<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Livewire\Storefront\Actions\Logout;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Home::class)->name('home');

Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
Route::livewire('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
Route::livewire('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
Route::livewire('/cart', CartShow::class)->name('storefront.cart.show');
Route::livewire('/search', SearchIndex::class)->name('storefront.search.index');
Route::livewire('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');

Route::livewire('/checkout/{checkout}', CheckoutShow::class)->name('storefront.checkout.show');
Route::livewire('/checkout/{checkout}/confirmation', Confirmation::class)->name('storefront.checkout.confirmation');

Route::middleware('guest:customer')->group(function (): void {
    Route::livewire('/account/login', Login::class)->name('storefront.account.login');
    Route::livewire('/account/register', Register::class)->name('storefront.account.register');
});

Route::middleware('auth:customer')->prefix('account')->name('storefront.account.')->group(function (): void {
    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/orders', OrdersIndex::class)->name('orders.index');
    Route::livewire('/orders/{order:order_number}', OrdersShow::class)->name('orders.show');
    Route::livewire('/addresses', AddressesIndex::class)->name('addresses.index');
    Route::post('/logout', Logout::class)->name('logout');
});
