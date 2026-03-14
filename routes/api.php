<?php

use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('storefront/v1')
    ->middleware(['store.resolve'])
    ->group(function () {
        // Carts
        Route::post('/carts', [CartController::class, 'store'])->name('api.storefront.carts.store');
        Route::get('/carts/{cart}', [CartController::class, 'show'])->name('api.storefront.carts.show');
        Route::post('/carts/{cart}/lines', [CartController::class, 'addLine'])->name('api.storefront.carts.lines.store');
        Route::put('/carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->name('api.storefront.carts.lines.update');
        Route::delete('/carts/{cart}/lines/{line}', [CartController::class, 'removeLine'])->name('api.storefront.carts.lines.destroy');

        // Checkouts
        Route::post('/checkouts', [CheckoutController::class, 'store'])->name('api.storefront.checkouts.store');
        Route::get('/checkouts/{checkout}', [CheckoutController::class, 'show'])->name('api.storefront.checkouts.show');
        Route::put('/checkouts/{checkout}/address', [CheckoutController::class, 'setAddress'])->name('api.storefront.checkouts.address');
        Route::put('/checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod'])->name('api.storefront.checkouts.shipping');
        Route::put('/checkouts/{checkout}/payment-method', [CheckoutController::class, 'selectPaymentMethod'])->name('api.storefront.checkouts.payment');
    });

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/v1/stores/{store}')
    ->middleware(['auth'])
    ->group(function () {
        // Products
        Route::get('/products', [ProductController::class, 'index'])->name('api.admin.products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('api.admin.products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.admin.products.show');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('api.admin.products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('api.admin.products.destroy');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('api.admin.orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.admin.orders.show');
    });
