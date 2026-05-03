<?php

use App\Livewire\Admin\Analytics\Index as AdminAnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AdminAppsIndex;
use App\Livewire\Admin\Apps\Show as AdminAppsShow;
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
use App\Livewire\Admin\Search\Settings as AdminSearchSettings;
use App\Livewire\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemesEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::get('/login', AdminLogin::class)
        ->middleware('guest')
        ->name('admin.login');

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->middleware('auth')->name('admin.logout');

    Route::middleware(['auth', 'verified', 'admin'])
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', AdminDashboard::class)->name('dashboard');
            Route::get('/products', AdminProductsIndex::class)->name('products.index');
            Route::get('/products/create', AdminProductsForm::class)->name('products.create');
            Route::get('/products/{product}/edit', AdminProductsForm::class)->name('products.edit');
            Route::get('/collections', AdminCollectionsIndex::class)->name('collections.index');
            Route::get('/collections/create', AdminCollectionsForm::class)->name('collections.create');
            Route::get('/collections/{collection}/edit', AdminCollectionsForm::class)->name('collections.edit');
            Route::get('/inventory', AdminInventoryIndex::class)->name('inventory.index');
            Route::get('/orders', AdminOrdersIndex::class)->name('orders.index');
            Route::get('/orders/{order}', AdminOrdersShow::class)->name('orders.show');
            Route::get('/customers', AdminCustomersIndex::class)->name('customers.index');
            Route::get('/customers/{customer}', AdminCustomersShow::class)->name('customers.show');
            Route::get('/discounts', AdminDiscountsIndex::class)->name('discounts.index');
            Route::get('/discounts/create', AdminDiscountsForm::class)->name('discounts.create');
            Route::get('/discounts/{discount}/edit', AdminDiscountsForm::class)->name('discounts.edit');
            Route::get('/settings', AdminSettingsIndex::class)->name('settings.index');
            Route::get('/settings/shipping', AdminSettingsShipping::class)->name('settings.shipping');
            Route::get('/settings/taxes', AdminSettingsTaxes::class)->name('settings.taxes');
            Route::get('/themes', AdminThemesIndex::class)->name('themes.index');
            Route::get('/themes/{theme}/editor', AdminThemesEditor::class)->name('themes.editor');
            Route::get('/pages', AdminPagesIndex::class)->name('pages.index');
            Route::get('/pages/create', AdminPagesForm::class)->name('pages.create');
            Route::get('/pages/{page}/edit', AdminPagesForm::class)->name('pages.edit');
            Route::get('/navigation', AdminNavigationIndex::class)->name('navigation.index');
            Route::get('/analytics', AdminAnalyticsIndex::class)->name('analytics.index');
            Route::get('/search/settings', AdminSearchSettings::class)->name('search.settings');
            Route::get('/apps', AdminAppsIndex::class)->name('apps.index');
            Route::get('/apps/{installation}', AdminAppsShow::class)->name('apps.show');
            Route::get('/developers', AdminDevelopersIndex::class)->name('developers.index');
        });
});
