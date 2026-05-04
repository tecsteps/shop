<?php

use App\Http\Controllers\Api\Admin\V1\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\V1\OrderFulfillmentController as AdminOrderFulfillmentController;
use App\Http\Controllers\Api\Admin\V1\OrderRefundController as AdminOrderRefundController;
use App\Http\Controllers\Api\Storefront\V1\CartController;
use App\Http\Controllers\Api\Storefront\V1\CartLineController;
use App\Http\Controllers\Api\Storefront\V1\CheckoutController;
use App\Http\Controllers\Api\Storefront\V1\OrderController as StorefrontOrderController;
use App\Http\Controllers\Api\Storefront\V1\SearchController as StorefrontSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('store.resolve')
    ->prefix('storefront/v1')
    ->name('api.storefront.v1.')
    ->group(function (): void {
        Route::middleware('throttle:api.storefront')->group(function (): void {
            Route::post('carts', [CartController::class, 'store'])->name('carts.store');
            Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
            Route::post('carts/{cart}/lines', [CartLineController::class, 'store'])->name('carts.lines.store');
            Route::put('carts/{cart}/lines/{cartLine}', [CartLineController::class, 'update'])->name('carts.lines.update');
            Route::delete('carts/{cart}/lines/{cartLine}', [CartLineController::class, 'destroy'])->name('carts.lines.destroy');
        });

        Route::middleware('throttle:search')->group(function (): void {
            Route::get('search', [StorefrontSearchController::class, 'index'])->name('search.index');
            Route::get('search/suggest', [StorefrontSearchController::class, 'suggest'])->name('search.suggest');
        });

        Route::middleware('throttle:checkout')->group(function (): void {
            Route::post('checkouts', [CheckoutController::class, 'store'])->name('checkouts.store');
            Route::get('checkouts/{checkout}', [CheckoutController::class, 'show'])->name('checkouts.show');
            Route::put('checkouts/{checkout}/address', [CheckoutController::class, 'address'])->name('checkouts.address');
            Route::put('checkouts/{checkout}/shipping-method', [CheckoutController::class, 'shippingMethod'])->name('checkouts.shipping-method');
            Route::post('checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('checkouts.apply-discount');
            Route::delete('checkouts/{checkout}/discount', [CheckoutController::class, 'destroyDiscount'])->name('checkouts.discount.destroy');
            Route::put('checkouts/{checkout}/payment-method', [CheckoutController::class, 'paymentMethod'])->name('checkouts.payment-method');
            Route::post('checkouts/{checkout}/pay', [CheckoutController::class, 'pay'])->name('checkouts.pay');
            Route::get('orders/{orderNumber}', [StorefrontOrderController::class, 'show'])->name('orders.show');
        });
    });

Route::middleware(['auth', 'throttle:60,1'])
    ->prefix('admin/v1/stores/{store}')
    ->name('api.admin.v1.')
    ->group(function (): void {
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/fulfillments', [AdminOrderFulfillmentController::class, 'store'])->name('orders.fulfillments.store');
        Route::post('orders/{order}/refunds', [AdminOrderRefundController::class, 'store'])->name('orders.refunds.store');
    });
