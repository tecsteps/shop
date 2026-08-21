<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\StorefrontAnalyticsController;
use App\Http\Controllers\Api\StorefrontCartController;
use App\Http\Controllers\Api\StorefrontCheckoutController;
use App\Http\Controllers\Api\StorefrontOrderController;
use App\Http\Controllers\Api\StorefrontSearchController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')->middleware([StartSession::class, 'store.resolve', 'throttle:api.storefront'])->group(function (): void {
    Route::post('carts', [StorefrontCartController::class, 'store']);
    Route::get('carts/{cartId}', [StorefrontCartController::class, 'show']);
    Route::post('carts/{cartId}/lines', [StorefrontCartController::class, 'addLine']);
    Route::put('carts/{cartId}/lines/{lineId}', [StorefrontCartController::class, 'updateLine']);
    Route::delete('carts/{cartId}/lines/{lineId}', [StorefrontCartController::class, 'removeLine']);
    Route::post('checkouts', [StorefrontCheckoutController::class, 'store'])->middleware('throttle:checkout');
    Route::get('checkouts/{checkoutId}', [StorefrontCheckoutController::class, 'show'])->middleware('throttle:checkout');
    Route::put('checkouts/{checkoutId}/address', [StorefrontCheckoutController::class, 'address'])->middleware('throttle:checkout');
    Route::put('checkouts/{checkoutId}/shipping-method', [StorefrontCheckoutController::class, 'shippingMethod'])->middleware('throttle:checkout');
    Route::put('checkouts/{checkoutId}/payment-method', [StorefrontCheckoutController::class, 'paymentMethod'])->middleware('throttle:checkout');
    Route::post('checkouts/{checkoutId}/apply-discount', [StorefrontCheckoutController::class, 'applyDiscount'])->middleware('throttle:checkout');
    Route::delete('checkouts/{checkoutId}/discount', [StorefrontCheckoutController::class, 'removeDiscount'])->middleware('throttle:checkout');
    Route::post('checkouts/{checkoutId}/pay', [StorefrontCheckoutController::class, 'pay'])->middleware('throttle:checkout');
    Route::get('orders/{orderNumber}', [StorefrontOrderController::class, 'show']);
    Route::get('search', [StorefrontSearchController::class, 'index'])->middleware('throttle:search');
    Route::get('search/suggest', [StorefrontSearchController::class, 'suggest'])->middleware('throttle:search');
    Route::post('analytics/events', [StorefrontAnalyticsController::class, 'store'])->middleware('throttle:analytics');
});

Route::prefix('admin/v1')->middleware(['auth:sanctum', 'api.ability', 'throttle:api.admin'])->group(function (): void {
    Route::post('platform/organizations', [PlatformController::class, 'storeOrganization']);
    Route::post('platform/stores', [PlatformController::class, 'storeStore']);
});

Route::prefix('admin/v1/stores/{storeId}')->middleware(['auth:sanctum', 'store.resolve', 'role.check:owner,admin,staff,support', 'throttle:api.admin'])->group(function (): void {
    Route::get('me', [PlatformController::class, 'me']);
});

Route::prefix('admin/v1/stores/{storeId}')->middleware(['auth:sanctum', 'store.resolve', 'role.check:owner,admin,staff,support', 'api.ability', 'throttle:api.admin'])->group(function (): void {
    Route::post('invites', [PlatformController::class, 'invite'])->middleware('role.check:owner,admin');
    Route::get('products', [AdminController::class, 'products']);
    Route::post('products', [AdminController::class, 'storeProduct'])->middleware('role.check:owner,admin,staff');
    Route::get('products/{productId}', [AdminController::class, 'showProduct']);
    Route::put('products/{productId}', [AdminController::class, 'updateProduct'])->middleware('role.check:owner,admin,staff');
    Route::delete('products/{productId}', [AdminController::class, 'deleteProduct'])->middleware('role.check:owner,admin');
    Route::post('products/{productId}/media/presign-upload', [PlatformController::class, 'presignMediaUpload'])->middleware('role.check:owner,admin,staff');
    Route::post('products/{productId}/media/{mediaId}/complete', [PlatformController::class, 'completeMediaUpload'])->middleware('role.check:owner,admin,staff');
    Route::get('collections', [AdminController::class, 'collections'])->middleware('role.check:owner,admin,staff');
    Route::post('collections', [AdminController::class, 'storeCollection'])->middleware('role.check:owner,admin,staff');
    Route::put('collections/{collectionId}', [AdminController::class, 'updateCollection'])->middleware('role.check:owner,admin,staff');
    Route::delete('collections/{collectionId}', [AdminController::class, 'deleteCollection'])->middleware('role.check:owner,admin');
    Route::get('orders', [AdminController::class, 'orders']);
    Route::get('orders/{orderId}', [AdminController::class, 'showOrder']);
    Route::get('customers', [AdminController::class, 'customers']);
    Route::get('discounts', [AdminController::class, 'discounts'])->middleware('role.check:owner,admin,staff');
    Route::post('discounts', [AdminController::class, 'storeDiscount'])->middleware('role.check:owner,admin,staff');
    Route::put('discounts/{discountId}', [AdminController::class, 'updateDiscount'])->middleware('role.check:owner,admin,staff');
    Route::delete('discounts/{discountId}', [AdminController::class, 'deleteDiscount'])->middleware('role.check:owner,admin');
    Route::get('shipping/zones', [AdminController::class, 'shippingZones'])->middleware('role.check:owner,admin');
    Route::post('shipping/zones', [AdminController::class, 'storeShippingZone'])->middleware('role.check:owner,admin');
    Route::put('shipping/zones/{zoneId}', [AdminController::class, 'updateShippingZone'])->middleware('role.check:owner,admin');
    Route::post('shipping/zones/{zoneId}/rates', [AdminController::class, 'storeShippingRate'])->middleware('role.check:owner,admin');
    Route::get('tax/settings', [AdminController::class, 'taxSettings'])->middleware('role.check:owner,admin');
    Route::put('tax/settings', [AdminController::class, 'updateTaxSettings'])->middleware('role.check:owner,admin');
    Route::get('pages', [AdminController::class, 'pages'])->middleware('role.check:owner,admin,staff');
    Route::post('pages', [AdminController::class, 'storePage'])->middleware('role.check:owner,admin,staff');
    Route::put('pages/{pageId}', [AdminController::class, 'updatePage'])->middleware('role.check:owner,admin,staff');
    Route::delete('pages/{pageId}', [AdminController::class, 'deletePage'])->middleware('role.check:owner,admin');
    Route::post('themes', [AdminController::class, 'storeTheme'])->middleware('role.check:owner,admin');
    Route::post('themes/{themeId}/publish', [AdminController::class, 'publishTheme'])->middleware('role.check:owner,admin');
    Route::put('themes/{themeId}/settings', [AdminController::class, 'updateThemeSettings'])->middleware('role.check:owner,admin');
    Route::post('search/reindex', [AdminController::class, 'reindex'])->middleware('role.check:owner,admin');
    Route::get('search/status', [AdminController::class, 'searchStatus'])->middleware('role.check:owner,admin');
    Route::get('analytics/summary', [AdminController::class, 'analyticsSummary'])->middleware('role.check:owner,admin,staff');
    Route::post('exports/orders', [AdminController::class, 'createOrderExport']);
    Route::get('exports/{exportId}', [AdminController::class, 'showOrderExport']);
    Route::post('orders/{orderId}/fulfillments', [AdminController::class, 'fulfillOrder'])->middleware('role.check:owner,admin,staff');
    Route::post('orders/{orderId}/refunds', [AdminController::class, 'refundOrder'])->middleware('role.check:owner,admin');
});
