<?php

use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Api\Admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\Api\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PlatformController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\Storefront\AnalyticsController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController as StorefrontOrderController;
use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')->middleware(['storefront.api', 'throttle:api.storefront'])->group(function (): void {
    Route::post('carts', [CartController::class, 'store']);
    Route::get('carts/{cartId}', [CartController::class, 'show']);
    Route::post('carts/{cartId}/lines', [CartController::class, 'addLine']);
    Route::put('carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine']);
    Route::delete('carts/{cartId}/lines/{lineId}', [CartController::class, 'removeLine']);

    Route::middleware('throttle:checkout')->group(function (): void {
        Route::post('checkouts', [CheckoutController::class, 'store']);
        Route::get('checkouts/{checkoutId}', [CheckoutController::class, 'show']);
        Route::put('checkouts/{checkoutId}/address', [CheckoutController::class, 'address']);
        Route::put('checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'shipping']);
        Route::put('checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'payment']);
        Route::post('checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'discount']);
        Route::delete('checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount']);
        Route::post('checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay']);
    });

    Route::get('search', [SearchController::class, 'index'])->middleware('throttle:search');
    Route::get('search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:search');
    Route::post('analytics/events', [AnalyticsController::class, 'store'])->middleware('throttle:analytics');
    Route::get('orders/{orderNumber}', [StorefrontOrderController::class, 'show']);
});

Route::prefix('admin/v1')->middleware(['auth:sanctum', 'throttle:api.admin'])->group(function (): void {
    Route::post('platform/organizations', [PlatformController::class, 'organization'])->middleware('abilities:manage-platform');
    Route::post('platform/stores', [PlatformController::class, 'store'])->middleware('abilities:manage-platform');

    Route::prefix('stores/{storeId}')->middleware('store.resolve')->group(function (): void {
        Route::get('me', [PlatformController::class, 'me']);
        Route::post('invites', [PlatformController::class, 'invite'])->middleware('abilities:manage-platform');

        Route::get('products', [AdminProductController::class, 'index'])->middleware('abilities:read-products');
        Route::post('products', [AdminProductController::class, 'store'])->middleware('abilities:write-products');
        Route::get('products/{productId}', [AdminProductController::class, 'show'])->middleware('abilities:read-products');
        Route::put('products/{productId}', [AdminProductController::class, 'update'])->middleware('abilities:write-products');
        Route::delete('products/{productId}', [AdminProductController::class, 'destroy'])->middleware('abilities:write-products');
        Route::post('products/{productId}/media/presign-upload', [AdminMediaController::class, 'presign'])->middleware('abilities:write-products');

        Route::get('collections', [AdminCollectionController::class, 'index'])->middleware('abilities:read-collections');
        Route::post('collections', [AdminCollectionController::class, 'store'])->middleware('abilities:write-collections');
        Route::put('collections/{collectionId}', [AdminCollectionController::class, 'update'])->middleware('abilities:write-collections');
        Route::delete('collections/{collectionId}', [AdminCollectionController::class, 'destroy'])->middleware('abilities:write-collections');

        Route::get('orders', [AdminOrderController::class, 'index'])->middleware('abilities:read-orders');
        Route::get('orders/{orderId}', [AdminOrderController::class, 'show'])->middleware('abilities:read-orders');
        Route::post('orders/{orderId}/fulfillments', [AdminOrderController::class, 'fulfill'])->middleware('abilities:write-orders');
        Route::post('orders/{orderId}/refunds', [AdminOrderController::class, 'refund'])->middleware('abilities:write-orders');
        Route::post('orders/{orderId}/confirm-payment', [AdminOrderController::class, 'confirmPayment'])->middleware('abilities:write-orders');

        Route::get('discounts', [AdminDiscountController::class, 'index'])->middleware('abilities:read-discounts');
        Route::post('discounts', [AdminDiscountController::class, 'store'])->middleware('abilities:write-discounts');
        Route::put('discounts/{discountId}', [AdminDiscountController::class, 'update'])->middleware('abilities:write-discounts');
        Route::delete('discounts/{discountId}', [AdminDiscountController::class, 'destroy'])->middleware('abilities:write-discounts');

        Route::get('shipping/zones', [AdminSettingsController::class, 'zones'])->middleware('abilities:read-settings');
        Route::post('shipping/zones', [AdminSettingsController::class, 'storeZone'])->middleware('abilities:write-settings');
        Route::put('shipping/zones/{zoneId}', [AdminSettingsController::class, 'updateZone'])->middleware('abilities:write-settings');
        Route::post('shipping/zones/{zoneId}/rates', [AdminSettingsController::class, 'storeRate'])->middleware('abilities:write-settings');
        Route::get('tax/settings', [AdminSettingsController::class, 'tax'])->middleware('abilities:read-settings');
        Route::put('tax/settings', [AdminSettingsController::class, 'updateTax'])->middleware('abilities:write-settings');

        Route::get('pages', [AdminContentController::class, 'pages'])->middleware('abilities:read-content');
        Route::post('pages', [AdminContentController::class, 'storePage'])->middleware('abilities:write-content');
        Route::put('pages/{pageId}', [AdminContentController::class, 'updatePage'])->middleware('abilities:write-content');
        Route::delete('pages/{pageId}', [AdminContentController::class, 'destroyPage'])->middleware('abilities:write-content');
        Route::post('themes', [AdminContentController::class, 'storeTheme'])->middleware('abilities:write-themes');
        Route::post('themes/{themeId}/publish', [AdminContentController::class, 'publishTheme'])->middleware('abilities:write-themes');
        Route::put('themes/{themeId}/settings', [AdminContentController::class, 'updateThemeSettings'])->middleware('abilities:write-themes');
        Route::post('search/reindex', [AdminContentController::class, 'reindex'])->middleware('abilities:write-settings');
        Route::get('search/status', [AdminContentController::class, 'searchStatus'])->middleware('abilities:read-settings');
        Route::get('analytics/summary', [AdminAnalyticsController::class, 'summary'])->middleware('abilities:read-analytics');
        Route::post('exports/orders', [AdminAnalyticsController::class, 'exportOrders'])->middleware('abilities:read-orders');
        Route::get('exports/{exportId}', [AdminAnalyticsController::class, 'export'])->middleware('abilities:read-orders')->name('api.admin.exports.show');
    });
});

Route::put('admin/v1/media/{media}/upload', [AdminMediaController::class, 'upload'])
    ->middleware('signed')
    ->name('api.media.upload');

Route::prefix('apps/v1')->group(function (): void {
    Route::get('stores/{storeId}/{resource}', fn () => response()->json([
        'message' => 'The OAuth app ecosystem is not implemented in this release.',
    ], 501))->where('resource', 'products|orders|customers');
});
