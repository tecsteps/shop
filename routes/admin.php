<?php

use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Apps\Show as AppsShow;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Collections\Index as CollectionsIndex;
use App\Livewire\Admin\Customers\Index as CustomersIndex;
use App\Livewire\Admin\Customers\Show as CustomersShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Search\Settings as SearchSettings;
use App\Livewire\Admin\Settings\General as SettingsGeneral;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Livewire\Admin\Themes\Editor as ThemeEditor;
use App\Livewire\Admin\Themes\Index as ThemesIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| The admin panel. Auth pages use only the `web` group. Authenticated pages add
| `auth` plus the `admin` middleware group, which resolves the current store
| from the session (current_store_id) and verifies the user's membership. With
| the store bound to the container, route-model binding for store-scoped models
| (products, orders, discounts, …) is automatically constrained to that store.
|
*/

Route::prefix('admin')->group(function () {
    // Guest-only authentication pages.
    Route::middleware('guest:web')->group(function () {
        Route::livewire('/login', AdminLogin::class)->name('admin.login');
    });

    Route::post('/logout', AdminLogout::class)
        ->middleware('auth:web')
        ->name('admin.logout');

    // Authenticated admin pages (store resolved from session).
    Route::middleware(['auth:web', 'admin'])->group(function () {
        Route::livewire('/', Dashboard::class)->name('admin.dashboard');

        // Products.
        Route::livewire('/products', ProductsIndex::class)->name('admin.products.index');
        Route::livewire('/products/create', ProductForm::class)->name('admin.products.create');
        Route::livewire('/products/{product}/edit', ProductForm::class)->name('admin.products.edit');

        // Collections.
        Route::livewire('/collections', CollectionsIndex::class)->name('admin.collections.index');
        Route::livewire('/collections/create', CollectionForm::class)->name('admin.collections.create');
        Route::livewire('/collections/{collection}/edit', CollectionForm::class)->name('admin.collections.edit');

        // Inventory.
        Route::livewire('/inventory', InventoryIndex::class)->name('admin.inventory.index');

        // Orders.
        Route::livewire('/orders', OrdersIndex::class)->name('admin.orders.index');
        Route::livewire('/orders/{order}', OrdersShow::class)->name('admin.orders.show');

        // Customers.
        Route::livewire('/customers', CustomersIndex::class)->name('admin.customers.index');
        Route::livewire('/customers/{customer}', CustomersShow::class)->name('admin.customers.show');

        // Discounts.
        Route::livewire('/discounts', DiscountsIndex::class)->name('admin.discounts.index');
        Route::livewire('/discounts/create', DiscountForm::class)->name('admin.discounts.create');
        Route::livewire('/discounts/{discount}/edit', DiscountForm::class)->name('admin.discounts.edit');

        // Content.
        Route::livewire('/pages', PagesIndex::class)->name('admin.pages.index');
        Route::livewire('/pages/create', PageForm::class)->name('admin.pages.create');
        Route::livewire('/pages/{page}/edit', PageForm::class)->name('admin.pages.edit');
        Route::livewire('/navigation', NavigationIndex::class)->name('admin.navigation.index');
        Route::livewire('/themes', ThemesIndex::class)->name('admin.themes.index');
        Route::livewire('/themes/{theme}/editor', ThemeEditor::class)->name('admin.themes.editor');

        // Settings (General is the index; sub-pages have their own routes).
        Route::livewire('/settings', SettingsGeneral::class)->name('admin.settings.index');
        Route::livewire('/settings/shipping', SettingsShipping::class)->name('admin.settings.shipping');
        Route::livewire('/settings/taxes', SettingsTaxes::class)->name('admin.settings.taxes');

        // Analytics, Search, Apps, Developers.
        Route::livewire('/analytics', AnalyticsIndex::class)->name('admin.analytics.index');
        Route::livewire('/search/settings', SearchSettings::class)->name('admin.search.settings');
        Route::livewire('/apps', AppsIndex::class)->name('admin.apps.index');
        Route::livewire('/apps/{appId}', AppsShow::class)->name('admin.apps.show');
        Route::livewire('/developers', DevelopersIndex::class)->name('admin.developers.index');
    });
});
