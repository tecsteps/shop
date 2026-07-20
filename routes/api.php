<?php

use App\Http\Controllers\Api\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PlatformController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Storefront\AnalyticsController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController;
use App\Http\Controllers\Api\Storefront\SearchController;
use App\Http\Middleware\ResolveStoreFromRoute;
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

        // Search endpoints (spec 02 §2.5) with the stricter search rate limit on top.
        Route::middleware('throttle:search')->group(function (): void {
            Route::get('/search', [SearchController::class, 'index']);
            Route::get('/search/suggest', [SearchController::class, 'suggest']);
        });

        // Analytics event ingestion (spec 02 §2.6) with the analytics rate limit on top.
        Route::post('/analytics/events', [AnalyticsController::class, 'store'])
            ->middleware('throttle:analytics');
    });

// Admin REST API (Sanctum personal access tokens, spec 02 §3). Store
// membership is resolved from the {storeId} route parameter because API
// tokens carry no session.
Route::middleware(['auth:sanctum', ResolveStoreFromRoute::class, 'throttle:api.admin'])
    ->prefix('admin/v1')
    ->group(function (): void {
        // Platform management (spec 02 §3.1).
        Route::post('/platform/organizations', [PlatformController::class, 'createOrganization'])
            ->middleware('ability:manage-platform');
        Route::post('/platform/stores', [PlatformController::class, 'createStore'])
            ->middleware('ability:manage-platform');
        Route::post('/stores/{storeId}/invites', [PlatformController::class, 'invite'])
            ->middleware('ability:manage-platform');
        Route::get('/stores/{storeId}/me', [PlatformController::class, 'me']);

        // Products (spec 02 §3.2).
        Route::get('/stores/{storeId}/products', [AdminProductController::class, 'index'])
            ->middleware('ability:read-products');
        Route::post('/stores/{storeId}/products', [AdminProductController::class, 'store'])
            ->middleware('ability:write-products');
        Route::get('/stores/{storeId}/products/{productId}', [AdminProductController::class, 'show'])
            ->middleware('ability:read-products');
        Route::put('/stores/{storeId}/products/{productId}', [AdminProductController::class, 'update'])
            ->middleware('ability:write-products');
        Route::delete('/stores/{storeId}/products/{productId}', [AdminProductController::class, 'destroy'])
            ->middleware('ability:write-products');
        Route::post('/stores/{storeId}/products/{productId}/media/presign-upload', [AdminProductController::class, 'presignUpload'])
            ->middleware('ability:write-products');

        // Collections (spec 02 §3.3).
        Route::get('/stores/{storeId}/collections', [AdminCollectionController::class, 'index'])
            ->middleware('ability:read-collections');
        Route::post('/stores/{storeId}/collections', [AdminCollectionController::class, 'store'])
            ->middleware('ability:write-collections');
        Route::put('/stores/{storeId}/collections/{collectionId}', [AdminCollectionController::class, 'update'])
            ->middleware('ability:write-collections');
        Route::delete('/stores/{storeId}/collections/{collectionId}', [AdminCollectionController::class, 'destroy'])
            ->middleware('ability:write-collections');

        // Orders (spec 02 §3.4) and CSV export (spec 05 §11.6). The export
        // route must precede the {orderId} show route.
        Route::get('/stores/{storeId}/orders', [AdminOrderController::class, 'index'])
            ->middleware('ability:read-orders');
        Route::get('/stores/{storeId}/orders/export', [AdminOrderController::class, 'export'])
            ->middleware('ability:read-orders');
        Route::get('/stores/{storeId}/orders/{orderId}', [AdminOrderController::class, 'show'])
            ->middleware('ability:read-orders');
        Route::post('/stores/{storeId}/orders/{orderId}/fulfillments', [AdminOrderController::class, 'storeFulfillment'])
            ->middleware('ability:write-orders');
        Route::post('/stores/{storeId}/orders/{orderId}/refunds', [AdminOrderController::class, 'storeRefund'])
            ->middleware('ability:write-orders');
    });
