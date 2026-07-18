<?php

use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Collections\Index as CollectionsIndex;
use App\Livewire\Admin\Customers\Index as CustomersIndex;
use App\Livewire\Admin\Customers\Show as CustomerShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Taxes;
use App\Livewire\Admin\Themes\Index as ThemesIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::livewire('/login', Login::class)->name('login');
        Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
        Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    });

    Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');

        Route::livewire('/', Dashboard::class)->name('dashboard');
        Route::livewire('/products', ProductsIndex::class)->name('products.index');
        Route::livewire('/products/create', ProductForm::class)->name('products.create');
        Route::livewire('/products/{product}/edit', ProductForm::class)->name('products.edit');
        Route::livewire('/collections', CollectionsIndex::class)->name('collections.index');
        Route::livewire('/collections/create', CollectionForm::class)->name('collections.create');
        Route::livewire('/collections/{collection}/edit', CollectionForm::class)->name('collections.edit');
        Route::livewire('/inventory', InventoryIndex::class)->name('inventory.index');
        Route::livewire('/orders', OrdersIndex::class)->name('orders.index');
        Route::livewire('/orders/{order}', OrderShow::class)->name('orders.show');
        Route::livewire('/customers', CustomersIndex::class)->name('customers.index');
        Route::livewire('/customers/{customer}', CustomerShow::class)->name('customers.show');
        Route::livewire('/discounts', DiscountsIndex::class)->name('discounts.index');
        Route::livewire('/discounts/create', DiscountForm::class)->name('discounts.create');
        Route::livewire('/discounts/{discount}/edit', DiscountForm::class)->name('discounts.edit');
        Route::livewire('/settings', SettingsIndex::class)->name('settings.index');
        Route::livewire('/settings/shipping', Shipping::class)->name('settings.shipping');
        Route::livewire('/settings/taxes', Taxes::class)->name('settings.taxes');
        Route::livewire('/pages', PagesIndex::class)->name('pages.index');
        Route::livewire('/pages/create', PageForm::class)->name('pages.create');
        Route::livewire('/pages/{page}/edit', PageForm::class)->name('pages.edit');
        Route::livewire('/themes', ThemesIndex::class)->name('themes.index');
        Route::livewire('/navigation', NavigationIndex::class)->name('navigation.index');
        Route::livewire('/analytics', AnalyticsIndex::class)->name('analytics.index');
        Route::livewire('/apps', AppsIndex::class)->name('apps.index');
        Route::livewire('/developers', DevelopersIndex::class)->name('developers.index');
    });
});
