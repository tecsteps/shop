<?php

use App\Http\Controllers\Admin\Auth\ForgotPasswordController as AdminForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController as AdminResetPasswordController;
use App\Http\Controllers\Admin\StoreSelectorController;
use App\Http\Controllers\Api\OauthStubController;
use App\Http\Controllers\Storefront\Auth\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Storefront\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Storefront\Auth\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Storefront\Auth\ResetPasswordController as CustomerResetPasswordController;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Apps\Show as AppsShow;
use App\Livewire\Admin\Collections\Form as AdminCollectionsForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as CustomersIndex;
use App\Livewire\Admin\Customers\Show as CustomersShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Discounts\Form as DiscountsForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Livewire\Admin\Pages\Form as PagesForm;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Livewire\Admin\Products\Form as ProductsForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Search\Settings as SearchSettings;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Livewire\Admin\Themes\Editor as ThemesEditor;
use App\Livewire\Admin\Themes\Index as ThemesIndex;
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
    ->middleware(['customer.redirect', 'auth', 'verified'])
    ->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');
        Route::get('forgot-password', [AdminForgotPasswordController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [AdminForgotPasswordController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [AdminResetPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [AdminResetPasswordController::class, 'store'])->name('password.update');
    });

    Route::post('logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')->group(function (): void {
        Route::get('stores/select', [StoreSelectorController::class, 'index'])->name('store-selector');
        Route::post('stores/select', [StoreSelectorController::class, 'store'])->name('store-selector.store');
    });

    Route::middleware(['auth', 'verified', 'store.resolve', 'role.check:owner,admin,staff,support'])->group(function (): void {
        Route::livewire('/', Dashboard::class)->name('dashboard');

        Route::livewire('products', ProductsIndex::class)->name('products.index');
        Route::livewire('products/create', ProductsForm::class)->name('products.create');
        Route::livewire('products/{product}/edit', ProductsForm::class)->name('products.edit');

        Route::livewire('inventory', InventoryIndex::class)->name('inventory.index');

        Route::livewire('collections', AdminCollectionsIndex::class)->name('collections.index');
        Route::livewire('collections/create', AdminCollectionsForm::class)->name('collections.create');
        Route::livewire('collections/{collection}/edit', AdminCollectionsForm::class)->name('collections.edit');

        Route::livewire('orders', OrdersIndex::class)->name('orders.index');
        Route::livewire('orders/{order}', OrdersShow::class)->name('orders.show');

        Route::livewire('customers', CustomersIndex::class)->name('customers.index');
        Route::livewire('customers/{customer}', CustomersShow::class)->name('customers.show');

        Route::livewire('discounts', DiscountsIndex::class)->name('discounts.index');
        Route::livewire('discounts/create', DiscountsForm::class)->name('discounts.create');
        Route::livewire('discounts/{discount}/edit', DiscountsForm::class)->name('discounts.edit');

        Route::livewire('settings', SettingsIndex::class)->name('settings.index');
        Route::livewire('settings/shipping', SettingsShipping::class)->name('settings.shipping');
        Route::livewire('settings/taxes', SettingsTaxes::class)->name('settings.taxes');

        Route::livewire('themes', ThemesIndex::class)->name('themes.index');
        Route::livewire('themes/{theme}/editor', ThemesEditor::class)->name('themes.editor');

        Route::livewire('pages', PagesIndex::class)->name('pages.index');
        Route::livewire('pages/create', PagesForm::class)->name('pages.create');
        Route::livewire('pages/{page}/edit', PagesForm::class)->name('pages.edit');

        Route::livewire('navigation', NavigationIndex::class)->name('navigation.index');

        Route::livewire('apps', AppsIndex::class)->name('apps.index');
        Route::livewire('apps/{installation}', AppsShow::class)->name('apps.show');

        Route::livewire('developers', DevelopersIndex::class)->name('developers.index');

        Route::livewire('analytics', AnalyticsIndex::class)->name('analytics.index');

        Route::livewire('search/settings', SearchSettings::class)->name('search.settings');
    });
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

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('oauth/authorize', [OauthStubController::class, 'authorize'])->name('oauth.authorize');
});

Route::post('oauth/token', [OauthStubController::class, 'token'])->name('oauth.token');
