<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\StoreSelectorController;
use App\Http\Controllers\Storefront\Auth\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Storefront\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Storefront\Auth\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Storefront\Auth\ResetPasswordController as CustomerResetPasswordController;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddressesIndex;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrdersShow;
use App\Livewire\Storefront\Account\Profile as AccountProfile;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

Route::livewire('/', StorefrontHome::class)->name('home')->middleware('store.resolve');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');
    });

    Route::post('logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')->group(function (): void {
        Route::get('stores/select', [StoreSelectorController::class, 'index'])->name('store-selector');
        Route::post('stores/select', [StoreSelectorController::class, 'store'])->name('store-selector.store');
    });

    Route::view('/', 'admin.dashboard')
        ->middleware(['auth', 'verified', 'store.resolve', 'role.check:owner,admin,staff,support'])
        ->name('dashboard');
});

Route::middleware('store.resolve')->group(function (): void {
    Route::livewire('collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::livewire('products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::livewire('cart', CartShow::class)->name('storefront.cart');
    Route::livewire('search', SearchIndex::class)->name('storefront.search');
    Route::livewire('pages/{handle}', PagesShow::class)->name('storefront.pages.show');

    Route::livewire('checkout/{checkoutId}', CheckoutShow::class)->name('storefront.checkout.show');
    Route::livewire('checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');

    Route::middleware('guest:customer')->group(function (): void {
        Route::get('account/login', [CustomerLoginController::class, 'create'])->name('customer.login');
        Route::post('account/login', [CustomerLoginController::class, 'store'])
            ->middleware('throttle:login')
            ->name('customer.login.store');
        Route::get('account/register', [CustomerRegisterController::class, 'create'])->name('customer.register');
        Route::post('account/register', [CustomerRegisterController::class, 'store'])->name('customer.register.store');
        Route::get('account/forgot-password', [CustomerForgotPasswordController::class, 'create'])->name('customer.password.request');
        Route::post('account/forgot-password', [CustomerForgotPasswordController::class, 'store'])->name('customer.password.email');
        Route::get('account/reset-password/{token}', [CustomerResetPasswordController::class, 'create'])->name('customer.password.reset');
        Route::post('account/reset-password', [CustomerResetPasswordController::class, 'store'])->name('customer.password.update');
    });

    Route::post('account/logout', [CustomerLoginController::class, 'destroy'])->name('customer.logout');

    Route::middleware('customer.auth')->group(function (): void {
        Route::livewire('account', AccountDashboard::class)->name('customer.account');
        Route::livewire('account/profile', AccountProfile::class)->name('customer.profile');
        Route::livewire('account/orders', AccountOrdersIndex::class)->name('customer.orders.index');
        Route::livewire('account/orders/{orderNumber}', AccountOrdersShow::class)->name('customer.orders.show');
        Route::livewire('account/addresses', AccountAddressesIndex::class)->name('customer.addresses');
    });
});

require __DIR__.'/settings.php';
