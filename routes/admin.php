<?php

use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Apps\Show as AppsShow;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as CollectionsForm;
use App\Livewire\Admin\Collections\Index as CollectionsIndex;
use App\Livewire\Admin\Customers\Index as CustomersIndex;
use App\Livewire\Admin\Customers\Show as CustomersShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Discounts\Form as DiscountsForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
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
use Illuminate\Support\Facades\Route;

Route::get('/login', AdminLogin::class)->name('login');

Route::middleware(['auth', 'resolve.store:admin'])->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/products', ProductsIndex::class)->name('products.index');
    Route::get('/products/new', ProductsForm::class)->name('products.create');
    Route::get('/products/{product}', ProductsForm::class)->name('products.edit');

    Route::get('/orders', OrdersIndex::class)->name('orders.index');
    Route::get('/orders/{order}', OrdersShow::class)->name('orders.show');

    Route::get('/collections', CollectionsIndex::class)->name('collections.index');
    Route::get('/collections/new', CollectionsForm::class)->name('collections.create');
    Route::get('/collections/{collection}', CollectionsForm::class)->name('collections.edit');

    Route::get('/customers', CustomersIndex::class)->name('customers.index');
    Route::get('/customers/{customer}', CustomersShow::class)->name('customers.show');

    Route::get('/discounts', DiscountsIndex::class)->name('discounts.index');
    Route::get('/discounts/new', DiscountsForm::class)->name('discounts.create');
    Route::get('/discounts/{discount}', DiscountsForm::class)->name('discounts.edit');

    Route::get('/analytics', AnalyticsIndex::class)->name('analytics.index');

    Route::get('/content/pages', PagesIndex::class)->name('pages.index');
    Route::get('/content/pages/new', PagesForm::class)->name('pages.create');
    Route::get('/content/pages/{page}', PagesForm::class)->name('pages.edit');

    Route::get('/content/navigation', NavigationIndex::class)->name('navigation.index');

    Route::get('/content/themes', ThemesIndex::class)->name('themes.index');
    Route::get('/content/themes/{theme}', ThemesEditor::class)->name('themes.editor');

    Route::get('/settings', SettingsIndex::class)->name('settings.index');
    Route::get('/settings/shipping', SettingsShipping::class)->name('settings.shipping');
    Route::get('/settings/taxes', SettingsTaxes::class)->name('settings.taxes');

    Route::get('/search/settings', SearchSettings::class)->name('search.settings');

    Route::get('/apps', AppsIndex::class)->name('apps.index');
    Route::get('/apps/{installation}', AppsShow::class)->name('apps.show');

    Route::get('/developers', DevelopersIndex::class)->name('developers.index');

    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('logout');
});
