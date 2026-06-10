<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Storefront\Auth\CustomerLoginController;
use App\Http\Controllers\Storefront\Auth\CustomerRegisterController;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as AdminCollectionsForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomersShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopersIndex;
use App\Livewire\Admin\Discounts\Form as AdminDiscountsForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Inventory\Index as AdminInventoryIndex;
use App\Livewire\Admin\Navigation\Index as AdminNavigationIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Pages\Form as AdminPagesForm;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Form as AdminProductsForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemesEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrders;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrderShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (no store resolution)
|--------------------------------------------------------------------------
*/

Route::livewire('/admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('/admin/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('admin.login.attempt');

Route::post('/admin/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (session-based store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::livewire('/admin', AdminDashboard::class)->name('admin.dashboard');

    Route::livewire('/admin/products', AdminProductsIndex::class)->name('admin.products.index');
    Route::livewire('/admin/products/create', AdminProductsForm::class)->name('admin.products.create');
    Route::livewire('/admin/products/{productId}/edit', AdminProductsForm::class)
        ->whereNumber('productId')
        ->name('admin.products.edit');

    Route::livewire('/admin/orders', AdminOrdersIndex::class)->name('admin.orders.index');
    Route::livewire('/admin/orders/{order}', AdminOrdersShow::class)
        ->whereNumber('order')
        ->name('admin.orders.show');

    Route::livewire('/admin/customers', AdminCustomersIndex::class)->name('admin.customers.index');
    Route::livewire('/admin/customers/{customer}', AdminCustomersShow::class)
        ->whereNumber('customer')
        ->name('admin.customers.show');

    Route::livewire('/admin/collections', AdminCollectionsIndex::class)->name('admin.collections.index');
    Route::livewire('/admin/collections/create', AdminCollectionsForm::class)->name('admin.collections.create');
    Route::livewire('/admin/collections/{collectionId}/edit', AdminCollectionsForm::class)
        ->whereNumber('collectionId')
        ->name('admin.collections.edit');

    Route::livewire('/admin/inventory', AdminInventoryIndex::class)->name('admin.inventory.index');

    Route::livewire('/admin/discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
    Route::livewire('/admin/discounts/create', AdminDiscountsForm::class)->name('admin.discounts.create');
    Route::livewire('/admin/discounts/{discountId}/edit', AdminDiscountsForm::class)
        ->whereNumber('discountId')
        ->name('admin.discounts.edit');

    Route::livewire('/admin/settings', AdminSettingsIndex::class)->name('admin.settings.index');
    Route::livewire('/admin/settings/shipping', AdminSettingsShipping::class)->name('admin.settings.shipping');
    Route::livewire('/admin/settings/taxes', AdminSettingsTaxes::class)->name('admin.settings.taxes');

    Route::livewire('/admin/themes', AdminThemesIndex::class)->name('admin.themes.index');
    Route::livewire('/admin/themes/{themeId}/editor', AdminThemesEditor::class)
        ->whereNumber('themeId')
        ->name('admin.themes.editor');

    Route::livewire('/admin/pages', AdminPagesIndex::class)->name('admin.pages.index');
    Route::livewire('/admin/pages/create', AdminPagesForm::class)->name('admin.pages.create');
    Route::livewire('/admin/pages/{pageId}/edit', AdminPagesForm::class)
        ->whereNumber('pageId')
        ->name('admin.pages.edit');

    Route::livewire('/admin/navigation', AdminNavigationIndex::class)->name('admin.navigation.index');

    Route::livewire('/admin/developers', AdminDevelopersIndex::class)->name('admin.developers.index');
});

/*
|--------------------------------------------------------------------------
| Storefront Routes (hostname-based store resolution)
|--------------------------------------------------------------------------
*/

Route::middleware('storefront')->group(function (): void {
    Route::livewire('/', Home::class)->name('home');

    Route::livewire('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::livewire('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::livewire('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::livewire('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');

    Route::livewire('/cart', CartShow::class)->name('storefront.cart');
    Route::livewire('/checkout', CheckoutShow::class)->name('storefront.checkout');
    Route::livewire('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.confirmation');

    Route::livewire('/account/login', CustomerLogin::class)->name('storefront.account.login');
    Route::post('/account/login', [CustomerLoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('storefront.account.login.attempt');

    Route::livewire('/account/register', CustomerRegister::class)->name('storefront.account.register');
    Route::post('/account/register', [CustomerRegisterController::class, 'store'])
        ->name('storefront.account.register.attempt');

    Route::post('/account/logout', [CustomerLoginController::class, 'destroy'])
        ->name('storefront.account.logout');

    Route::middleware('auth:customer')->group(function (): void {
        Route::livewire('/account', AccountDashboard::class)->name('storefront.account.index');
        Route::livewire('/account/orders', AccountOrders::class)->name('storefront.account.orders.index');
        Route::livewire('/account/orders/{orderNumber}', AccountOrderShow::class)->name('storefront.account.orders.show');
        Route::livewire('/account/addresses', AccountAddresses::class)->name('storefront.account.addresses.index');
    });
});
