<?php

use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

// Admin API
Route::prefix('admin/v1')
    ->middleware(['auth:sanctum', 'throttle:api.admin'])
    ->group(function () {
        // Placeholder for admin API routes
    });

// Storefront API
Route::prefix('storefront/v1')
    ->middleware(['storefront', 'throttle:api.storefront'])
    ->group(function () {
        // Cart endpoints
        Route::post('carts', [CartController::class, 'store']);
        Route::get('carts/{cartId}', [CartController::class, 'show']);
        Route::post('carts/{cartId}/lines', [CartController::class, 'addLine']);
        Route::put('carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine']);
        Route::delete('carts/{cartId}/lines/{lineId}', [CartController::class, 'deleteLine']);

        // Checkout endpoints
        Route::post('checkouts', [CheckoutController::class, 'store']);
        Route::get('checkouts/{checkoutId}', [CheckoutController::class, 'show']);
        Route::put('checkouts/{checkoutId}/address', [CheckoutController::class, 'setAddress']);
        Route::put('checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
        Route::put('checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'selectPaymentMethod']);
        Route::post('checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::delete('checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount']);
    });
