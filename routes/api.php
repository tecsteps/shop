<?php

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\OrderFulfillmentController as AdminOrderFulfillmentController;
use App\Http\Controllers\Api\Admin\OrderRefundController as AdminOrderRefundController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront REST API (spec 02 section 2)
|--------------------------------------------------------------------------
|
| The store is resolved from the request hostname. Cart endpoints use the
| api.storefront limiter (120/min per IP); checkout endpoints use the
| checkout limiter (10/min per session).
|
*/

Route::prefix('storefront/v1')
    ->name('api.storefront.')
    ->middleware('store.resolve:storefront')
    ->group(function (): void {
        Route::middleware('throttle:api.storefront')->group(function (): void {
            Route::post('/carts', [CartController::class, 'store'])->name('carts.store');
            Route::get('/carts/{cartId}', [CartController::class, 'show'])
                ->whereNumber('cartId')
                ->name('carts.show');
            Route::post('/carts/{cartId}/lines', [CartController::class, 'storeLine'])
                ->whereNumber('cartId')
                ->name('carts.lines.store');
            Route::put('/carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine'])
                ->whereNumber('cartId')
                ->whereNumber('lineId')
                ->name('carts.lines.update');
            Route::delete('/carts/{cartId}/lines/{lineId}', [CartController::class, 'destroyLine'])
                ->whereNumber('cartId')
                ->whereNumber('lineId')
                ->name('carts.lines.destroy');
        });

        Route::middleware('throttle:checkout')->whereNumber('checkoutId')->group(function (): void {
            Route::post('/checkouts', [CheckoutController::class, 'store'])->name('checkouts.store');
            Route::get('/checkouts/{checkoutId}', [CheckoutController::class, 'show'])->name('checkouts.show');
            Route::put('/checkouts/{checkoutId}/address', [CheckoutController::class, 'updateAddress'])->name('checkouts.address');
            Route::put('/checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'updateShippingMethod'])->name('checkouts.shipping-method');
            Route::post('/checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('checkouts.apply-discount');
            Route::delete('/checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount'])->name('checkouts.remove-discount');
            Route::put('/checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'updatePaymentMethod'])->name('checkouts.payment-method');
            Route::post('/checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay'])->name('checkouts.pay');
        });
    });

/*
|--------------------------------------------------------------------------
| Admin REST API (spec 02 section 3)
|--------------------------------------------------------------------------
|
| Sanctum token authentication; the store is bound from the {storeId} route
| parameter after verifying membership. Token abilities are enforced
| per endpoint (spec 06 section 1.3).
|
*/

Route::prefix('admin/v1/stores/{storeId}')
    ->name('api.admin.')
    ->whereNumber('storeId')
    ->middleware(['auth:sanctum', 'store.resolve:api-admin', 'throttle:api.admin'])
    ->group(function (): void {
        Route::get('/products', [AdminProductController::class, 'index'])
            ->middleware('abilities:read-products')
            ->name('products.index');
        Route::post('/products', [AdminProductController::class, 'store'])
            ->middleware('abilities:write-products')
            ->name('products.store');
        Route::get('/products/{productId}', [AdminProductController::class, 'show'])
            ->whereNumber('productId')
            ->middleware('abilities:read-products')
            ->name('products.show');
        Route::put('/products/{productId}', [AdminProductController::class, 'update'])
            ->whereNumber('productId')
            ->middleware('abilities:write-products')
            ->name('products.update');
        Route::delete('/products/{productId}', [AdminProductController::class, 'destroy'])
            ->whereNumber('productId')
            ->middleware('abilities:write-products')
            ->name('products.destroy');

        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->middleware('abilities:read-orders')
            ->name('orders.index');
        Route::get('/orders/{orderId}', [AdminOrderController::class, 'show'])
            ->whereNumber('orderId')
            ->middleware('abilities:read-orders')
            ->name('orders.show');
        Route::post('/orders/{orderId}/fulfillments', [AdminOrderFulfillmentController::class, 'store'])
            ->whereNumber('orderId')
            ->middleware('abilities:write-orders')
            ->name('orders.fulfillments.store');
        Route::post('/orders/{orderId}/refunds', [AdminOrderRefundController::class, 'store'])
            ->whereNumber('orderId')
            ->middleware('abilities:write-orders')
            ->name('orders.refunds.store');
    });
