<?php

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
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
