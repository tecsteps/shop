<?php

use App\Http\Controllers\Api\Admin\V1\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\V1\ProductController as AdminProductController;
use App\Http\Controllers\Api\Storefront\V1\CartController;
use App\Http\Controllers\Api\Storefront\V1\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('storefront/v1')
    ->middleware(['resolve.store', 'throttle:api.storefront'])
    ->group(function (): void {
        // Cart endpoints
        Route::post('carts', [CartController::class, 'store']);
        Route::get('carts/{cart}', [CartController::class, 'show']);
        Route::post('carts/{cart}/lines', [CartController::class, 'addLine']);
        Route::put('carts/{cart}/lines/{line}', [CartController::class, 'updateLine']);
        Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'removeLine']);

        // Checkout endpoints
        Route::post('checkouts', [CheckoutController::class, 'store']);
        Route::get('checkouts/{checkout}', [CheckoutController::class, 'show']);
        Route::put('checkouts/{checkout}/address', [CheckoutController::class, 'setAddress']);
        Route::put('checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
        Route::post('checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::put('checkouts/{checkout}/payment-method', [CheckoutController::class, 'setPaymentMethod']);
        Route::post('checkouts/{checkout}/pay', [CheckoutController::class, 'pay']);
    });

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/v1/stores/{store}')
    ->middleware(['auth:sanctum', 'throttle:api.admin'])
    ->group(function (): void {
        // Product endpoints
        Route::get('products', [AdminProductController::class, 'index'])->middleware('ability:read-products');
        Route::post('products', [AdminProductController::class, 'store'])->middleware('ability:write-products');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->middleware('ability:read-products');
        Route::put('products/{product}', [AdminProductController::class, 'update'])->middleware('ability:write-products');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('ability:write-products');

        // Order endpoints
        Route::get('orders', [AdminOrderController::class, 'index'])->middleware('ability:read-orders');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->middleware('ability:read-orders');
        Route::post('orders/{order}/fulfillments', [AdminOrderController::class, 'createFulfillment'])->middleware('ability:write-orders');
        Route::post('orders/{order}/refunds', [AdminOrderController::class, 'createRefund'])->middleware('ability:write-orders');
    });
