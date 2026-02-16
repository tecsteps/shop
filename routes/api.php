<?php

use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(['store.resolve', 'throttle:120,1'])
    ->prefix('storefront/v1')
    ->group(function (): void {
        Route::post('/carts', [CartController::class, 'store']);
        Route::get('/carts/{cart}', [CartController::class, 'show']);
        Route::post('/carts/{cart}/lines', [CartController::class, 'addLine']);
        Route::put('/carts/{cart}/lines/{line}', [CartController::class, 'updateLine']);
        Route::delete('/carts/{cart}/lines/{line}', [CartController::class, 'removeLine']);

        Route::post('/checkouts', [CheckoutController::class, 'store']);
        Route::get('/checkouts/{checkout}', [CheckoutController::class, 'show']);
        Route::put('/checkouts/{checkout}/address', [CheckoutController::class, 'setAddress']);
        Route::put('/checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
        Route::post('/checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::put('/checkouts/{checkout}/payment-method', [CheckoutController::class, 'setPaymentMethod']);
        Route::post('/checkouts/{checkout}/pay', [CheckoutController::class, 'pay']);
    });

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('admin/v1')
    ->group(function (): void {
        Route::get('/stores/{store}/products', [ProductController::class, 'index']);
        Route::post('/stores/{store}/products', [ProductController::class, 'store']);
        Route::put('/stores/{store}/products/{product}', [ProductController::class, 'update']);
        Route::delete('/stores/{store}/products/{product}', [ProductController::class, 'destroy']);

        Route::get('/stores/{store}/orders', [OrderController::class, 'index']);
        Route::get('/stores/{store}/orders/{order}', [OrderController::class, 'show']);
        Route::post('/stores/{store}/orders/{order}/fulfillments', [OrderController::class, 'createFulfillment']);
        Route::post('/stores/{store}/orders/{order}/refunds', [OrderController::class, 'createRefund']);
    });
