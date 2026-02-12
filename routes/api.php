<?php

use App\Http\Controllers\Api\Admin\V1\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\V1\ProductController as AdminProductController;
use App\Http\Controllers\Api\Storefront\V1\CartController;
use App\Http\Controllers\Api\Storefront\V1\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')
    ->middleware(['resolve.store:storefront'])
    ->group(function (): void {
        Route::post('/carts', [CartController::class, 'store']);
        Route::get('/carts/{cartId}', [CartController::class, 'show']);
        Route::post('/carts/{cartId}/lines', [CartController::class, 'addLine']);
        Route::put('/carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine']);
        Route::delete('/carts/{cartId}/lines/{lineId}', [CartController::class, 'destroyLine']);

        Route::post('/checkouts', [CheckoutController::class, 'store']);
        Route::get('/checkouts/{checkoutId}', [CheckoutController::class, 'show']);
        Route::put('/checkouts/{checkoutId}/address', [CheckoutController::class, 'updateAddress']);
        Route::put('/checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'updateShippingMethod']);
        Route::put('/checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'updatePaymentMethod']);
        Route::post('/checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::delete('/checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount']);
        Route::post('/checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay']);
    });

Route::prefix('admin/v1/stores/{storeId}')
    ->group(function (): void {
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::get('/products/{productId}', [AdminProductController::class, 'show']);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{orderId}', [AdminOrderController::class, 'show']);
    });
