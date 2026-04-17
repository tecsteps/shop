<?php

use App\Livewire\Admin\Analytics\Index as AdminAnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AdminAppsIndex;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Admin\Collections\Form as AdminCollectionForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomerShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopersIndex;
use App\Livewire\Admin\Discounts\Form as AdminDiscountForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Inventory\Index as AdminInventoryIndex;
use App\Livewire\Admin\Navigation\Index as AdminNavigationIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Livewire\Admin\Pages\Form as AdminPageForm;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Form as AdminProductForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Settings\General as AdminSettingsGeneral;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemeEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrderShow;
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

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin Auth Routes (no store resolution needed)
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)->name('admin.login');
    Route::post('logout', [AdminLogout::class, 'logout'])->name('admin.logout');
});

// Admin Routes (authenticated, store resolved from session)
Route::prefix('admin')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function () {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');

        // Products
        Route::get('products', AdminProductsIndex::class)->name('admin.products.index');
        Route::get('products/create', AdminProductForm::class)->name('admin.products.create');
        Route::get('products/{product}/edit', AdminProductForm::class)->name('admin.products.edit');

        // Collections
        Route::get('collections', AdminCollectionsIndex::class)->name('admin.collections.index');
        Route::get('collections/create', AdminCollectionForm::class)->name('admin.collections.create');
        Route::get('collections/{collection}/edit', AdminCollectionForm::class)->name('admin.collections.edit');

        // Inventory
        Route::get('inventory', AdminInventoryIndex::class)->name('admin.inventory.index');

        // Orders
        Route::get('orders', AdminOrdersIndex::class)->name('admin.orders.index');
        Route::get('orders/{order}', AdminOrderShow::class)->name('admin.orders.show');

        // Customers
        Route::get('customers', AdminCustomersIndex::class)->name('admin.customers.index');
        Route::get('customers/{customer}', AdminCustomerShow::class)->name('admin.customers.show');

        // Discounts
        Route::get('discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
        Route::get('discounts/create', AdminDiscountForm::class)->name('admin.discounts.create');
        Route::get('discounts/{discount}/edit', AdminDiscountForm::class)->name('admin.discounts.edit');

        // Settings
        Route::get('settings', AdminSettingsGeneral::class)->name('admin.settings.index');
        Route::get('settings/shipping', AdminSettingsShipping::class)->name('admin.settings.shipping');
        Route::get('settings/taxes', AdminSettingsTaxes::class)->name('admin.settings.taxes');

        // Themes
        Route::get('themes', AdminThemesIndex::class)->name('admin.themes.index');
        Route::get('themes/{theme}/editor', AdminThemeEditor::class)->name('admin.themes.editor');

        // Pages
        Route::get('pages', AdminPagesIndex::class)->name('admin.pages.index');
        Route::get('pages/create', AdminPageForm::class)->name('admin.pages.create');
        Route::get('pages/{page}/edit', AdminPageForm::class)->name('admin.pages.edit');

        // Navigation
        Route::get('navigation', AdminNavigationIndex::class)->name('admin.navigation.index');

        // Analytics
        Route::get('analytics', AdminAnalyticsIndex::class)->name('admin.analytics.index');

        // Apps
        Route::get('apps', AdminAppsIndex::class)->name('admin.apps.index');

        // Developers
        Route::get('developers', AdminDevelopersIndex::class)->name('admin.developers.index');
    });

// Storefront Routes (store resolved from hostname)
Route::middleware(['storefront'])->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::get('products/{handle}', ProductShow::class)->name('storefront.products.show');
    Route::get('pages/{handle}', PageShow::class)->name('storefront.pages.show');
    Route::get('search', SearchIndex::class)->name('storefront.search');

    Route::get('cart', CartShow::class)->name('storefront.cart.show');
    Route::get('checkout/{checkoutId}', CheckoutShow::class)->name('storefront.checkout.show');
    Route::get('checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');

    Route::get('account/login', CustomerLogin::class)->name('storefront.account.login');
    Route::get('account/register', CustomerRegister::class)->name('storefront.account.register');

    // Authenticated Customer Routes
    Route::middleware(['auth:customer'])->group(function () {
        Route::get('account', AccountDashboard::class)->name('storefront.account.dashboard');
        Route::get('account/orders', OrdersIndex::class)->name('storefront.account.orders');
        Route::get('account/orders/{orderNumber}', OrderShow::class)->name('storefront.account.orders.show');
        Route::get('account/addresses', AddressesIndex::class)->name('storefront.account.addresses');
    });
});

require __DIR__.'/settings.php';
