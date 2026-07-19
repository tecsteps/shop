<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use Illuminate\Support\Facades\Route;

// Storefront routes (spec 04).
Route::middleware(['store.resolve:storefront'])->group(function (): void {
    Route::livewire('/', Home::class)->name('storefront.home');
    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::livewire('/cart', CartShow::class)->name('storefront.cart.show');
    Route::livewire('/checkout/{checkoutId}', CheckoutShow::class)->name('storefront.checkout.show');
    Route::livewire('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
    Route::livewire('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');
});
