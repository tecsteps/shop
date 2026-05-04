<?php

use App\Http\Controllers\Api\Admin\V1\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\V1\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\V1\OrderFulfillmentController as AdminOrderFulfillmentController;
use App\Http\Controllers\Api\Admin\V1\OrderRefundController as AdminOrderRefundController;
use App\Http\Controllers\Api\Admin\V1\ProductController as AdminProductController;
use App\Http\Controllers\Api\Apps\V1\DeferredEndpointController as DeferredAppEndpointController;
use App\Http\Controllers\Api\Storefront\V1\AnalyticsEventController as StorefrontAnalyticsEventController;
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

        Route::middleware('throttle:analytics')->group(function (): void {
            Route::post('analytics/events', [StorefrontAnalyticsEventController::class, 'store'])->name('analytics.events.store');
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

Route::middleware('throttle:60,1')
    ->prefix('admin/v1/stores/{store}')
    ->name('api.admin.v1.')
    ->group(function (): void {
        Route::middleware('admin.api:read-products')->group(function (): void {
            Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
            Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        });

        Route::middleware('admin.api:read-customers')->group(function (): void {
            Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        });

        Route::middleware('admin.api:read-orders')->group(function (): void {
            Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware('admin.api:write-orders')->group(function (): void {
            Route::post('orders/{order}/fulfillments', [AdminOrderFulfillmentController::class, 'store'])->name('orders.fulfillments.store');
            Route::post('orders/{order}/refunds', [AdminOrderRefundController::class, 'store'])->name('orders.refunds.store');
        });
    });

Route::middleware('throttle:60,1')
    ->prefix('apps/v1/stores/{store}')
    ->name('api.apps.v1.')
    ->group(function (): void {
        Route::get('products', DeferredAppEndpointController::class)->name('products.index');
        Route::get('orders', DeferredAppEndpointController::class)->name('orders.index');
        Route::get('customers', DeferredAppEndpointController::class)->name('customers.index');
    });
