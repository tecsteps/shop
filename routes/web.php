<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\AdminLogoutController;
use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\CustomerLogoutController;
use App\Http\Controllers\Auth\CustomerRegisterController;
use App\Livewire\Admin\Analytics\Index as AdminAnalytics;
use App\Livewire\Admin\Apps\Index as AdminApps;
use App\Livewire\Admin\Apps\Show as AdminAppsShow;
use App\Livewire\Admin\Collections\Form as AdminCollectionsForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomersShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopers;
use App\Livewire\Admin\Discounts\Form as AdminDiscountsForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Inventory\Index as AdminInventory;
use App\Livewire\Admin\Navigation\Index as AdminNavigation;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Pages\Form as AdminPagesForm;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Form as AdminProductsForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Search\Settings as AdminSearchSettings;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Settings\Shipping as AdminShipping;
use App\Livewire\Admin\Settings\Taxes as AdminTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemesEditor;
use App\Livewire\Admin\Themes\Index as AdminThemes;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrdersShow;
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

// Admin auth (public)
Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store'])->middleware('throttle:login');
Route::post('/admin/logout', [AdminLogoutController::class, '__invoke'])->middleware('auth')->name('admin.logout');

// Admin panel (authenticated)
Route::middleware(['auth', 'verified', 'store.resolve', 'role.check'])->prefix('admin')->group(function () {
    Route::livewire('/', Dashboard::class)->name('admin.dashboard');
    Route::livewire('/products', AdminProductsIndex::class)->name('admin.products.index');
    Route::livewire('/products/create', AdminProductsForm::class)->name('admin.products.create');
    Route::livewire('/products/{product}/edit', AdminProductsForm::class)->name('admin.products.edit');
    Route::livewire('/inventory', AdminInventory::class)->name('admin.inventory.index');
    Route::livewire('/collections', AdminCollectionsIndex::class)->name('admin.collections.index');
    Route::livewire('/collections/create', AdminCollectionsForm::class)->name('admin.collections.create');
    Route::livewire('/collections/{collection}/edit', AdminCollectionsForm::class)->name('admin.collections.edit');
    Route::livewire('/orders', AdminOrdersIndex::class)->name('admin.orders.index');
    Route::livewire('/orders/{order}', AdminOrdersShow::class)->name('admin.orders.show');
    Route::livewire('/customers', AdminCustomersIndex::class)->name('admin.customers.index');
    Route::livewire('/customers/{customer}', AdminCustomersShow::class)->name('admin.customers.show');
    Route::livewire('/discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
    Route::livewire('/discounts/create', AdminDiscountsForm::class)->name('admin.discounts.create');
    Route::livewire('/discounts/{discount}/edit', AdminDiscountsForm::class)->name('admin.discounts.edit');
    Route::livewire('/settings', AdminSettings::class)->name('admin.settings.index');
    Route::livewire('/settings/shipping', AdminShipping::class)->name('admin.settings.shipping');
    Route::livewire('/settings/taxes', AdminTaxes::class)->name('admin.settings.taxes');
    Route::livewire('/themes', AdminThemes::class)->name('admin.themes.index');
    Route::livewire('/themes/{theme}/editor', AdminThemesEditor::class)->name('admin.themes.editor');
    Route::livewire('/pages', AdminPagesIndex::class)->name('admin.pages.index');
    Route::livewire('/pages/create', AdminPagesForm::class)->name('admin.pages.create');
    Route::livewire('/pages/{page}/edit', AdminPagesForm::class)->name('admin.pages.edit');
    Route::livewire('/navigation', AdminNavigation::class)->name('admin.navigation.index');
    Route::livewire('/apps', AdminApps::class)->name('admin.apps.index');
    Route::livewire('/apps/{installation}', AdminAppsShow::class)->name('admin.apps.show');
    Route::livewire('/developers', AdminDevelopers::class)->name('admin.developers.index');
    Route::livewire('/analytics', AdminAnalytics::class)->name('admin.analytics.index');
    Route::livewire('/search/settings', AdminSearchSettings::class)->name('admin.search.settings');
});

// Storefront (public)
Route::middleware(['store.resolve'])->group(function () {
    Route::livewire('/', Home::class)->name('storefront.home');
    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionsShow::class)->name('storefront.collection');
    Route::livewire('/products/{handle}', ProductsShow::class)->name('storefront.product');
    Route::livewire('/cart', CartShow::class)->name('storefront.cart');
    Route::livewire('/search', SearchIndex::class)->name('storefront.search');
    Route::livewire('/pages/{handle}', PagesShow::class)->name('storefront.page');
});

// Checkout (public)
Route::middleware(['store.resolve'])->group(function () {
    Route::livewire('/checkout/{checkoutId}', CheckoutShow::class)->name('storefront.checkout');
    Route::livewire('/checkout/{checkoutId}/confirmation', Confirmation::class)->name('storefront.checkout.confirmation');
});

// Customer auth (public)
Route::middleware(['store.resolve'])->group(function () {
    Route::get('/account/login', [CustomerLoginController::class, 'create'])->name('account.login');
    Route::post('/account/login', [CustomerLoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/account/register', [CustomerRegisterController::class, 'create'])->name('account.register');
    Route::post('/account/register', [CustomerRegisterController::class, 'store'])->middleware('throttle:login');
    Route::post('/account/logout', [CustomerLogoutController::class, '__invoke'])->name('account.logout');
    Route::get('/forgot-password', fn () => view('storefront.account.auth.forgot-password'))->name('account.forgot-password');
    Route::get('/reset-password/{token}', fn () => view('storefront.account.auth.reset-password'))->name('account.reset-password');
});

// Customer account (authenticated)
Route::middleware(['store.resolve', 'auth.customer'])->group(function () {
    Route::livewire('/account', AccountDashboard::class)->name('account.dashboard');
    Route::livewire('/account/orders', AccountOrdersIndex::class)->name('account.orders.index');
    Route::livewire('/account/orders/{orderNumber}', AccountOrdersShow::class)->name('account.orders.show');
    Route::livewire('/account/addresses', AccountAddresses::class)->name('account.addresses.index');
});
