<?php

use App\Http\Controllers\Api\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ShippingController as AdminShippingController;
use App\Http\Controllers\Api\Admin\TaxController as AdminTaxController;
use App\Http\Controllers\Api\Storefront\AnalyticsController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController;
use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

// Storefront API
Route::middleware(['store.resolve'])->prefix('storefront/v1')->group(function () {
    Route::post('/carts', [CartController::class, 'store'])->middleware('throttle:api.storefront');
    Route::get('/carts/{cart}', [CartController::class, 'show'])->middleware('throttle:api.storefront');
    Route::post('/carts/{cart}/lines', [CartController::class, 'addLine'])->middleware('throttle:api.storefront');
    Route::put('/carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->middleware('throttle:api.storefront');
    Route::delete('/carts/{cart}/lines/{line}', [CartController::class, 'removeLine'])->middleware('throttle:api.storefront');

    Route::post('/checkouts', [CheckoutController::class, 'store'])->middleware('throttle:checkout');
    Route::get('/checkouts/{checkout}', [CheckoutController::class, 'show'])->middleware('throttle:checkout');
    Route::put('/checkouts/{checkout}/address', [CheckoutController::class, 'setAddress'])->middleware('throttle:checkout');
    Route::put('/checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod'])->middleware('throttle:checkout');
    Route::put('/checkouts/{checkout}/payment-method', [CheckoutController::class, 'selectPaymentMethod'])->middleware('throttle:checkout');
    Route::post('/checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount'])->middleware('throttle:checkout');
    Route::delete('/checkouts/{checkout}/discount', [CheckoutController::class, 'removeDiscount'])->middleware('throttle:checkout');
    Route::post('/checkouts/{checkout}/pay', [CheckoutController::class, 'pay'])->middleware('throttle:checkout');

    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->middleware('throttle:api.storefront');

    Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:search');

    Route::post('/analytics/events', [AnalyticsController::class, 'store'])->middleware('throttle:analytics');
});

// Admin API
Route::middleware(['auth:sanctum', 'store.resolve'])->prefix('admin/v1')->group(function () {
    Route::middleware('throttle:api.admin')->group(function () {
        Route::get('/stores/{storeId}/products', [AdminProductController::class, 'index'])->middleware('abilities:read-products');
        Route::post('/stores/{storeId}/products', [AdminProductController::class, 'store'])->middleware('abilities:write-products');
        Route::get('/stores/{storeId}/products/{productId}', [AdminProductController::class, 'show'])->middleware('abilities:read-products');
        Route::put('/stores/{storeId}/products/{productId}', [AdminProductController::class, 'update'])->middleware('abilities:write-products');
        Route::delete('/stores/{storeId}/products/{productId}', [AdminProductController::class, 'destroy'])->middleware('abilities:write-products');

        Route::get('/stores/{storeId}/orders', [AdminOrderController::class, 'index'])->middleware('abilities:read-orders');
        Route::get('/stores/{storeId}/orders/{orderId}', [AdminOrderController::class, 'show'])->middleware('abilities:read-orders');
        Route::post('/stores/{storeId}/orders/{orderId}/fulfillments', [AdminOrderController::class, 'fulfill'])->middleware('abilities:write-orders');
        Route::post('/stores/{storeId}/orders/{orderId}/refunds', [AdminOrderController::class, 'refund'])->middleware('abilities:write-orders');

        Route::get('/stores/{storeId}/collections', [AdminCollectionController::class, 'index'])->middleware('abilities:read-collections');
        Route::post('/stores/{storeId}/collections', [AdminCollectionController::class, 'store'])->middleware('abilities:write-collections');
        Route::put('/stores/{storeId}/collections/{collectionId}', [AdminCollectionController::class, 'update'])->middleware('abilities:write-collections');
        Route::delete('/stores/{storeId}/collections/{collectionId}', [AdminCollectionController::class, 'destroy'])->middleware('abilities:write-collections');

        Route::get('/stores/{storeId}/discounts', [AdminDiscountController::class, 'index'])->middleware('abilities:read-discounts');
        Route::post('/stores/{storeId}/discounts', [AdminDiscountController::class, 'store'])->middleware('abilities:write-discounts');
        Route::put('/stores/{storeId}/discounts/{discountId}', [AdminDiscountController::class, 'update'])->middleware('abilities:write-discounts');
        Route::delete('/stores/{storeId}/discounts/{discountId}', [AdminDiscountController::class, 'destroy'])->middleware('abilities:write-discounts');

        Route::get('/stores/{storeId}/shipping/zones', [AdminShippingController::class, 'index'])->middleware('abilities:read-settings');
        Route::post('/stores/{storeId}/shipping/zones', [AdminShippingController::class, 'store'])->middleware('abilities:write-settings');

        Route::get('/stores/{storeId}/tax/settings', [AdminTaxController::class, 'show'])->middleware('abilities:read-settings');
        Route::put('/stores/{storeId}/tax/settings', [AdminTaxController::class, 'update'])->middleware('abilities:write-settings');

        Route::get('/stores/{storeId}/pages', [AdminPageController::class, 'index'])->middleware('abilities:read-content');
        Route::post('/stores/{storeId}/pages', [AdminPageController::class, 'store'])->middleware('abilities:write-content');
    });
});
