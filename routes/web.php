<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as AdminCollectionForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Inventory\Index as AdminInventoryIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Livewire\Admin\Products\Form as AdminProductForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Orders\Index as CustomerOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as CustomerOrderShow;
use App\Livewire\Storefront\Cart\Show as StorefrontCartShow;
use App\Livewire\Storefront\Checkout\Confirmation as StorefrontCheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as StorefrontCheckoutShow;
use App\Livewire\Storefront\Collections\Index as StorefrontCollectionsIndex;
use App\Livewire\Storefront\Collections\Show as StorefrontCollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as StorefrontPageShow;
use App\Livewire\Storefront\Products\Show as StorefrontProductShow;
use App\Livewire\Storefront\Search\Index as StorefrontSearchIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['storefront'])->group(function (): void {
    Route::livewire('/', StorefrontHome::class)->name('home');
    Route::livewire('collections', StorefrontCollectionsIndex::class)->name('collections.index');
    Route::livewire('collections/{handle}', StorefrontCollectionShow::class)->name('collections.show');
    Route::livewire('products/{handle}', StorefrontProductShow::class)->name('products.show');
    Route::livewire('cart', StorefrontCartShow::class)->name('cart.show');
    Route::livewire('checkout', StorefrontCheckoutShow::class)->name('checkout.show');
    Route::livewire('checkout/confirmation/{order}', StorefrontCheckoutConfirmation::class)->name('checkout.confirmation');
    Route::livewire('search', StorefrontSearchIndex::class)->name('search.index');
    Route::livewire('pages/{handle}', StorefrontPageShow::class)->name('pages.show');
});

Route::livewire('admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('admin/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('admin.login');
})->middleware('auth')->name('admin.logout');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::livewire('products', AdminProductsIndex::class)->name('products.index');
    Route::livewire('products/create', AdminProductForm::class)->name('products.create');
    Route::livewire('products/{product}/edit', AdminProductForm::class)->name('products.edit');
    Route::livewire('inventory', AdminInventoryIndex::class)->name('inventory.index');
    Route::livewire('orders', AdminOrdersIndex::class)->name('orders.index');
    Route::livewire('orders/{order}', AdminOrderShow::class)->name('orders.show');
    Route::livewire('collections', AdminCollectionsIndex::class)->name('collections.index');
    Route::livewire('collections/create', AdminCollectionForm::class)->name('collections.create');
    Route::livewire('collections/{collection}/edit', AdminCollectionForm::class)->name('collections.edit');
});

Route::middleware(['storefront'])->group(function (): void {
    Route::livewire('account/login', CustomerLogin::class)
        ->middleware('guest:customer')
        ->name('account.login');

    Route::livewire('account/register', CustomerRegister::class)
        ->middleware('guest:customer')
        ->name('account.register');

    Route::livewire('account', CustomerOrdersIndex::class)
        ->middleware('auth:customer')
        ->name('account.dashboard');

    Route::livewire('account/orders/{order}', CustomerOrderShow::class)
        ->middleware('auth:customer')
        ->name('account.orders.show');
});

Route::redirect('dashboard', 'admin')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
