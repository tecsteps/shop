<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AppsController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DevelopersController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SearchSettingsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ThemeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'show'])->name('login');
        Route::post('login', [AdminLoginController::class, 'login'])->name('login.submit');
        Route::post('logout', [AdminLoginController::class, 'logout'])->middleware('auth')->name('logout');
    });

Route::middleware(['auth', 'resolve.store:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('products', ProductController::class)->except(['show']);

        Route::resource('collections', CollectionController::class)->except(['show']);
        Route::resource('pages', PageController::class)->except(['show']);

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::put('inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/confirm-bank-transfer', [OrderController::class, 'confirmBankTransfer'])->name('orders.confirm-bank-transfer');
        Route::post('orders/{order}/fulfillments', [OrderController::class, 'storeFulfillment'])->name('orders.fulfillments.store');
        Route::post('orders/{order}/refunds', [OrderController::class, 'storeRefund'])->name('orders.refunds.store');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        Route::get('discounts', [DiscountController::class, 'index'])->name('discounts.index');
        Route::get('discounts/create', [DiscountController::class, 'create'])->name('discounts.create');
        Route::post('discounts', [DiscountController::class, 'store'])->name('discounts.store');
        Route::get('discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('discounts.edit');
        Route::put('discounts/{discount}', [DiscountController::class, 'update'])->name('discounts.update');

        Route::get('navigation', [NavigationController::class, 'index'])->name('navigation.index');
        Route::post('navigation/items', [NavigationController::class, 'store'])->name('navigation.items.store');
        Route::put('navigation/items/{item}', [NavigationController::class, 'update'])->name('navigation.items.update');
        Route::delete('navigation/items/{item}', [NavigationController::class, 'destroy'])->name('navigation.items.destroy');

        Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
        Route::get('themes/{theme}/edit', [ThemeController::class, 'edit'])->name('themes.edit');
        Route::put('themes/{theme}', [ThemeController::class, 'update'])->name('themes.update');
        Route::post('themes/{theme}/publish', [ThemeController::class, 'publish'])->name('themes.publish');

        Route::get('search/settings', [SearchSettingsController::class, 'edit'])->name('search.settings.edit');
        Route::put('search/settings', [SearchSettingsController::class, 'update'])->name('search.settings.update');
        Route::post('search/settings/reindex', [SearchSettingsController::class, 'reindex'])->name('search.settings.reindex');

        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('apps', [AppsController::class, 'index'])->name('apps.index');

        Route::get('developers', [DevelopersController::class, 'index'])->name('developers.index');
        Route::post('developers/tokens', [DevelopersController::class, 'storeToken'])->name('developers.tokens.store');
        Route::delete('developers/tokens/{tokenId}', [DevelopersController::class, 'destroyToken'])
            ->whereNumber('tokenId')
            ->name('developers.tokens.destroy');
        Route::post('developers/webhooks', [DevelopersController::class, 'storeWebhook'])->name('developers.webhooks.store');
        Route::put('developers/webhooks/{webhookId}', [DevelopersController::class, 'updateWebhook'])
            ->whereNumber('webhookId')
            ->name('developers.webhooks.update');
        Route::delete('developers/webhooks/{webhookId}', [DevelopersController::class, 'destroyWebhook'])
            ->whereNumber('webhookId')
            ->name('developers.webhooks.destroy');

        Route::get('settings', [SettingsController::class, 'general'])->name('settings.general');
        Route::put('settings', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');

        Route::get('settings/shipping', [SettingsController::class, 'shipping'])->name('settings.shipping');
        Route::put('settings/shipping', [SettingsController::class, 'updateShipping'])->name('settings.shipping.update');

        Route::get('settings/taxes', [SettingsController::class, 'taxes'])->name('settings.taxes');
        Route::put('settings/taxes', [SettingsController::class, 'updateTaxes'])->name('settings.taxes.update');
    });
