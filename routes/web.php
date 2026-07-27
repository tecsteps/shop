<?php

use App\Http\Controllers\Storefront\Account\LogoutController as AccountLogoutController;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddressesIndex;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as AccountForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as AccountLogin;
use App\Livewire\Storefront\Account\Auth\Register as AccountRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as AccountResetPassword;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrdersShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

// Storefront routes (spec 04).
Route::middleware(['store.resolve.storefront'])->group(function (): void {
    Route::livewire('/', Home::class)->name('storefront.home');
    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::livewire('/cart', CartShow::class)->name('storefront.cart.show');
    Route::livewire('/search', SearchIndex::class)->name('storefront.search');
    Route::livewire('/checkout/{checkoutId}', CheckoutShow::class)->name('storefront.checkout.show');
    Route::livewire('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
    Route::livewire('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');

    // Customer auth pages (spec 02 §1.3). Submissions are handled by Livewire actions.
    Route::livewire('/account/login', AccountLogin::class)->name('storefront.account.login');
    Route::livewire('/account/register', AccountRegister::class)->name('storefront.account.register');
    Route::livewire('/forgot-password', AccountForgotPassword::class)->name('storefront.password.request');
    Route::livewire('/reset-password/{token}', AccountResetPassword::class)->name('storefront.password.reset');

    Route::post('/account/logout', AccountLogoutController::class)->name('storefront.account.logout');

    // Customer account pages (spec 04 §10).
    Route::middleware(['auth.customer'])->group(function (): void {
        Route::livewire('/account', AccountDashboard::class)->name('storefront.account.dashboard');
        Route::livewire('/account/orders', AccountOrdersIndex::class)->name('storefront.account.orders.index');
        Route::livewire('/account/orders/{orderNumber}', AccountOrdersShow::class)->name('storefront.account.orders.show');
        Route::livewire('/account/addresses', AccountAddressesIndex::class)->name('storefront.account.addresses.index');
    });
});
