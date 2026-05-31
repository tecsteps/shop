<?php

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Storefront\AnalyticsController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| REST API endpoints. The admin API is token-authenticated via Sanctum and
| ability-gated per route; the storefront API is public, resolved by hostname,
| and rate limited per endpoint group (storefront/checkout/search/analytics).
| All monetary amounts are integers in minor units (cents).
|
*/

Route::prefix('api')->group(function () {
    /*
    | Admin REST API — Bearer (Sanctum) auth, store resolved from the {store}
    | route parameter, ability-gated per route.
    */
    Route::middleware(['auth:sanctum', 'store.resolve.route', 'throttle:api.admin'])
        ->prefix('admin/v1/stores/{store}')
        ->name('api.admin.')
        ->group(function () {
            // Products
            Route::get('products', [AdminProductController::class, 'index'])
                ->middleware('ability:read-products')->name('products.index');
            Route::post('products', [AdminProductController::class, 'store'])
                ->middleware('ability:write-products')->name('products.store');
            Route::get('products/{product}', [AdminProductController::class, 'show'])
                ->middleware('ability:read-products')->name('products.show');
            Route::put('products/{product}', [AdminProductController::class, 'update'])
                ->middleware('ability:write-products')->name('products.update');
            Route::delete('products/{product}', [AdminProductController::class, 'destroy'])
                ->middleware('ability:write-products')->name('products.destroy');

            // Orders
            Route::get('orders', [AdminOrderController::class, 'index'])
                ->middleware('ability:read-orders')->name('orders.index');
            Route::get('orders/{order}', [AdminOrderController::class, 'show'])
                ->middleware('ability:read-orders')->name('orders.show');
            Route::post('orders/{order}/fulfillments', [AdminOrderController::class, 'storeFulfillment'])
                ->middleware('ability:write-orders')->name('orders.fulfillments.store');
            Route::post('orders/{order}/refunds', [AdminOrderController::class, 'storeRefund'])
                ->middleware('ability:write-orders')->name('orders.refunds.store');
        });

    /*
    | Storefront REST API — public, store resolved from the request hostname.
    */
    Route::prefix('storefront/v1')
        ->middleware('store.resolve')
        ->name('api.storefront.')
        ->group(function () {
            // Carts
            Route::middleware('throttle:api.storefront')->group(function () {
                Route::post('carts', [CartController::class, 'store'])->name('carts.store');
                Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
                Route::post('carts/{cart}/lines', [CartController::class, 'storeLine'])->name('carts.lines.store');
                Route::put('carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->name('carts.lines.update');
                Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'destroyLine'])->name('carts.lines.destroy');
            });

            // Checkouts
            Route::middleware('throttle:checkout')->group(function () {
                Route::post('checkouts', [CheckoutController::class, 'store'])->name('checkouts.store');
                Route::get('checkouts/{checkout}', [CheckoutController::class, 'show'])->name('checkouts.show');
                Route::put('checkouts/{checkout}/address', [CheckoutController::class, 'setAddress'])->name('checkouts.address');
                Route::put('checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod'])->name('checkouts.shipping');
                Route::put('checkouts/{checkout}/payment-method', [CheckoutController::class, 'setPaymentMethod'])->name('checkouts.payment-method');
                Route::post('checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('checkouts.apply-discount');
                Route::delete('checkouts/{checkout}/discount', [CheckoutController::class, 'removeDiscount'])->name('checkouts.remove-discount');
                Route::post('checkouts/{checkout}/pay', [CheckoutController::class, 'pay'])->name('checkouts.pay');
            });

            // Search
            Route::middleware('throttle:search')->group(function () {
                Route::get('search', [SearchController::class, 'index'])->name('search');
                Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
            });

            // Analytics
            Route::middleware('throttle:analytics')->group(function () {
                Route::post('analytics/events', [AnalyticsController::class, 'store'])->name('analytics.events');
            });
        });
});
