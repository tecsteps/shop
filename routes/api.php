<?php

use App\Http\Controllers\Api\Admin\V1\OrderController;
use App\Http\Controllers\Api\Admin\V1\ProductController;
use App\Http\Controllers\Api\Storefront\V1\CartController;
use App\Http\Controllers\Api\Storefront\V1\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Storefront API v1 (public) and Admin API v1 (Sanctum-protected).
|
*/

// Storefront API v1
Route::middleware(['store.resolve', 'throttle:api.storefront'])
    ->prefix('storefront/v1')
    ->group(function (): void {
        // Cart endpoints
        Route::post('/carts', [CartController::class, 'store']);
        Route::get('/carts/{cartId}', [CartController::class, 'show']);
        Route::post('/carts/{cartId}/lines', [CartController::class, 'addLine']);
        Route::put('/carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine']);
        Route::delete('/carts/{cartId}/lines/{lineId}', [CartController::class, 'removeLine']);

        // Checkout endpoints
        Route::post('/checkouts', [CheckoutController::class, 'store']);
        Route::get('/checkouts/{checkoutId}', [CheckoutController::class, 'show']);
        Route::put('/checkouts/{checkoutId}/address', [CheckoutController::class, 'setAddress']);
        Route::put('/checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
        Route::post('/checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::put('/checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'selectPaymentMethod']);
        Route::post('/checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay']);
    });

// Admin API v1
Route::middleware(['auth:sanctum', 'throttle:api.admin'])
    ->prefix('admin/v1')
    ->group(function (): void {
        // Products
        Route::get('/stores/{storeId}/products', [ProductController::class, 'index']);
        Route::post('/stores/{storeId}/products', [ProductController::class, 'store']);
        Route::get('/stores/{storeId}/products/{productId}', [ProductController::class, 'show']);
        Route::put('/stores/{storeId}/products/{productId}', [ProductController::class, 'update']);
        Route::delete('/stores/{storeId}/products/{productId}', [ProductController::class, 'destroy']);

        // Orders
        Route::get('/stores/{storeId}/orders', [OrderController::class, 'index']);
        Route::get('/stores/{storeId}/orders/{orderId}', [OrderController::class, 'show']);
        Route::post('/stores/{storeId}/orders/{orderId}/fulfillments', [OrderController::class, 'createFulfillment']);
        Route::post('/stores/{storeId}/orders/{orderId}/refunds', [OrderController::class, 'createRefund']);
    });
