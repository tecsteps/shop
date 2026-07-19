<?php

use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController;
use Illuminate\Support\Facades\Route;

// Storefront API (cart, checkout, search). Endpoints are added in later phases.
Route::middleware(['store.resolve:storefront', 'throttle:api.storefront'])
    ->prefix('storefront/v1')
    ->group(function (): void {
        // Cart endpoints (spec 02 §2.1).
        Route::post('/carts', [CartController::class, 'create']);
        Route::get('/carts/{cartId}', [CartController::class, 'show']);
        Route::post('/carts/{cartId}/lines', [CartController::class, 'addLine']);
        Route::put('/carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine']);
        Route::delete('/carts/{cartId}/lines/{lineId}', [CartController::class, 'removeLine']);

        // Checkout endpoints (spec 02 §2.2) with the stricter checkout rate limit on top.
        Route::middleware('throttle:checkout')->group(function (): void {
            Route::post('/checkouts', [CheckoutController::class, 'create']);
            Route::get('/checkouts/{checkoutId}', [CheckoutController::class, 'show']);
            Route::put('/checkouts/{checkoutId}/address', [CheckoutController::class, 'setAddress']);
            Route::put('/checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
            Route::put('/checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'selectPaymentMethod']);
            Route::post('/checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount']);
            Route::delete('/checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount']);
            Route::post('/checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay']);
        });

        // Order status endpoint (spec 02 §2.4), token-authenticated.
        Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    });

// Admin REST API (Sanctum personal access tokens). Endpoints are added in later phases.
Route::middleware(['auth:sanctum', 'store.resolve:admin', 'throttle:api.admin'])
    ->prefix('admin/v1')
    ->group(function (): void {
        //
    });
