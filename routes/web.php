<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Settings\Webhooks\Deliveries as AdminWebhookDeliveries;
use App\Livewire\Admin\Settings\Webhooks\Edit as AdminWebhookEdit;
use App\Livewire\Admin\Settings\Webhooks\Index as AdminWebhooksIndex;
use App\Livewire\Storefront\Account\Addresses as StorefrontAccountAddresses;
use App\Livewire\Storefront\Account\Auth\EmailVerify as StorefrontEmailVerify;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as StorefrontForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as StorefrontLogin;
use App\Livewire\Storefront\Account\Auth\Logout as StorefrontLogout;
use App\Livewire\Storefront\Account\Auth\Register as StorefrontRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as StorefrontResetPassword;
use App\Livewire\Storefront\Account\Auth\SetPassword as StorefrontSetPassword;
use App\Livewire\Storefront\Account\Dashboard as StorefrontAccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as StorefrontAccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as StorefrontAccountOrdersShow;
use App\Livewire\Storefront\Account\Profile as StorefrontAccountProfile;
use App\Livewire\Storefront\Cart\Show as StorefrontCartShow;
use App\Livewire\Storefront\Checkout\Show as StorefrontCheckoutShow;
use App\Livewire\Storefront\Checkout\Success as StorefrontCheckoutSuccess;
use App\Livewire\Storefront\Collections\Show as StorefrontCollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as StorefrontPageShow;
use App\Livewire\Storefront\Products\Show as StorefrontProductShow;
use App\Livewire\Storefront\Search\Index as StorefrontSearch;
use Illuminate\Support\Facades\Route;

Route::view('welcome', 'welcome')->name('welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::livewire('login', AdminLogin::class)->name('login');
    });

    Route::middleware(['auth', 'verified', 'store.resolve:admin'])->group(function (): void {
        Route::view('/', 'admin.dashboard')->name('dashboard');
        Route::livewire('orders', AdminOrdersIndex::class)->name('orders.index');
        Route::livewire('orders/{order}', AdminOrdersShow::class)->name('orders.show');

        Route::livewire('settings/webhooks', AdminWebhooksIndex::class)->name('settings.webhooks.index');
        Route::livewire('settings/webhooks/create', AdminWebhookEdit::class)->name('settings.webhooks.create');
        Route::livewire('settings/webhooks/{subscription}/edit', AdminWebhookEdit::class)->name('settings.webhooks.edit');
        Route::livewire('settings/webhooks/{subscription}/deliveries', AdminWebhookDeliveries::class)->name('settings.webhooks.deliveries');
    });
});

Route::middleware('store.resolve:storefront')->group(function (): void {
    Route::prefix('account')->name('account.')->group(function (): void {
        Route::livewire('login', StorefrontLogin::class)->name('login');
        Route::livewire('register', StorefrontRegister::class)->name('register');
        Route::livewire('forgot-password', StorefrontForgotPassword::class)->name('password.request');
        Route::livewire('reset-password/{token}', StorefrontResetPassword::class)->name('password.reset');
        Route::livewire('set-password', StorefrontSetPassword::class)->name('password.set');
        Route::get('email/verify/{id}/{hash}', StorefrontEmailVerify::class)->name('verification.verify');
        Route::match(['get', 'post'], 'logout', StorefrontLogout::class)->name('logout');

        Route::middleware('auth:customer')->group(function (): void {
            Route::livewire('/', StorefrontAccountDashboard::class)->name('dashboard');
            Route::livewire('orders', StorefrontAccountOrdersIndex::class)->name('orders.index');
            Route::livewire('orders/{orderNumber}', StorefrontAccountOrdersShow::class)->name('orders.show');
            Route::livewire('addresses', StorefrontAccountAddresses::class)->name('addresses');
            Route::livewire('profile', StorefrontAccountProfile::class)->name('profile');
        });
    });

    Route::livewire('/', StorefrontHome::class)->name('home');
    Route::livewire('/search', StorefrontSearch::class)->name('storefront.search');
    Route::livewire('/collections/{handle}', StorefrontCollectionShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', StorefrontProductShow::class)->name('storefront.products.show');
    Route::livewire('/pages/{handle}', StorefrontPageShow::class)->name('storefront.pages.show');

    Route::livewire('/cart', StorefrontCartShow::class)->name('storefront.cart.show');
    Route::livewire('/checkout', StorefrontCheckoutShow::class)->name('storefront.checkout.show');
    Route::livewire('/checkout/success', StorefrontCheckoutSuccess::class)->name('storefront.checkout.success');
});
