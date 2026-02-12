<?php

use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'resolve.store:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('products', ProductController::class)->except(['show']);

        Route::resource('collections', CollectionController::class)->except(['show']);

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

        Route::get('settings', [SettingsController::class, 'general'])->name('settings.general');
        Route::put('settings', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');

        Route::get('settings/shipping', [SettingsController::class, 'shipping'])->name('settings.shipping');
        Route::put('settings/shipping', [SettingsController::class, 'updateShipping'])->name('settings.shipping.update');

        Route::get('settings/taxes', [SettingsController::class, 'taxes'])->name('settings.taxes');
        Route::put('settings/taxes', [SettingsController::class, 'updateTaxes'])->name('settings.taxes.update');
    });
