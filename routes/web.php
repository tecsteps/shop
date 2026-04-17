<?php

use App\Http\Controllers\Admin\StoreSwitcherController;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Edit as AdminCollectionsEdit;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomersShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Discounts\Edit as AdminDiscountsEdit;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Pages\Edit as AdminPagesEdit;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Create as AdminProductsCreate;
use App\Livewire\Admin\Products\Edit as AdminProductsEdit;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Settings\General as AdminSettingsGeneral;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Staff as AdminSettingsStaff;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Settings\Webhooks\Deliveries as AdminWebhookDeliveries;
use App\Livewire\Admin\Settings\Webhooks\Edit as AdminWebhookEdit;
use App\Livewire\Admin\Settings\Webhooks\Index as AdminWebhooksIndex;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
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

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('switch-store/{store}', StoreSwitcherController::class)->name('store.switch');
    });

    Route::middleware(['auth', 'verified', 'store.resolve:admin'])->group(function (): void {
        Route::get('/', AdminDashboard::class)->name('dashboard');

        Route::get('products', AdminProductsIndex::class)->name('products.index');
        Route::get('products/create', AdminProductsCreate::class)->name('products.create');
        Route::get('products/{product}/edit', AdminProductsEdit::class)->name('products.edit');

        Route::get('collections', AdminCollectionsIndex::class)->name('collections.index');
        Route::get('collections/create', AdminCollectionsEdit::class)->name('collections.create');
        Route::get('collections/{collection}/edit', AdminCollectionsEdit::class)->name('collections.edit');

        Route::get('orders', AdminOrdersIndex::class)->name('orders.index');
        Route::get('orders/{order}', AdminOrdersShow::class)->name('orders.show');

        Route::get('customers', AdminCustomersIndex::class)->name('customers.index');
        Route::get('customers/{customer}', AdminCustomersShow::class)->name('customers.show');

        Route::get('discounts', AdminDiscountsIndex::class)->name('discounts.index');
        Route::get('discounts/create', AdminDiscountsEdit::class)->name('discounts.create');
        Route::get('discounts/{discount}/edit', AdminDiscountsEdit::class)->name('discounts.edit');

        Route::get('pages', AdminPagesIndex::class)->name('pages.index');
        Route::get('pages/create', AdminPagesEdit::class)->name('pages.create');
        Route::get('pages/{page}/edit', AdminPagesEdit::class)->name('pages.edit');

        Route::get('themes', AdminThemesIndex::class)->name('themes.index');

        Route::get('settings', AdminSettingsGeneral::class)->name('settings.general');
        Route::get('settings/shipping', AdminSettingsShipping::class)->name('settings.shipping');
        Route::get('settings/taxes', AdminSettingsTaxes::class)->name('settings.taxes');
        Route::get('settings/staff', AdminSettingsStaff::class)->name('settings.staff');

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
