<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Cart\Show as CartShow;
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
*/

// Public storefront routes
Route::middleware(['store.resolve'])->group(function () {
    Route::get('/', Home::class)->name('storefront.home');
    Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', ProductShow::class)->name('storefront.products.show');
    Route::get('/cart', CartShow::class)->name('storefront.cart');
    Route::get('/search', SearchIndex::class)->name('storefront.search');
    Route::get('/pages/{handle}', PageShow::class)->name('storefront.pages.show');

    // Customer auth routes
    Route::get('/account/login', CustomerLogin::class)->name('customer.login');
    Route::get('/account/register', CustomerRegister::class)->name('customer.register');

    // Customer protected routes
    Route::middleware(['auth:customer'])->prefix('account')->group(function () {
        Route::get('/', AccountDashboard::class)->name('customer.dashboard');
    });
});
