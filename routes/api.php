<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\OrderExportController;
use App\Http\Controllers\Api\ProductMediaUploadController;
use App\Http\Controllers\Api\StoreConfigurationController;
use App\Http\Controllers\Api\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')->middleware(['store.resolve', 'throttle:api.storefront'])->name('api.storefront.')->group(function (): void {
    Route::post('/carts', [StorefrontController::class, 'createCart'])->name('carts.create');
    Route::get('/carts/{cartId}', [StorefrontController::class, 'showCart'])->whereNumber('cartId')->name('carts.show');
    Route::post('/carts/{cartId}/lines', [StorefrontController::class, 'addCartLine'])->whereNumber('cartId')->name('carts.lines.add');
    Route::put('/carts/{cartId}/lines/{lineId}', [StorefrontController::class, 'updateCartLine'])->whereNumber('cartId')->whereNumber('lineId')->name('carts.lines.update');
    Route::delete('/carts/{cartId}/lines/{lineId}', [StorefrontController::class, 'deleteCartLine'])->whereNumber('cartId')->whereNumber('lineId')->name('carts.lines.delete');
    Route::post('/checkouts', [StorefrontController::class, 'createCheckout'])->middleware('throttle:checkout')->name('checkouts.create');
    Route::get('/checkouts/{checkoutId}', [StorefrontController::class, 'showCheckout'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.show');
    Route::put('/checkouts/{checkoutId}/address', [StorefrontController::class, 'updateAddress'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.address');
    Route::put('/checkouts/{checkoutId}/shipping-method', [StorefrontController::class, 'updateShipping'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.shipping');
    Route::put('/checkouts/{checkoutId}/payment-method', [StorefrontController::class, 'updatePayment'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.payment');
    Route::post('/checkouts/{checkoutId}/apply-discount', [StorefrontController::class, 'applyDiscount'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.discount.apply');
    Route::delete('/checkouts/{checkoutId}/discount', [StorefrontController::class, 'removeDiscount'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.discount.remove');
    Route::post('/checkouts/{checkoutId}/pay', [StorefrontController::class, 'pay'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.pay');
    Route::get('/orders/{orderNumber}', [StorefrontController::class, 'showOrder'])->name('orders.show');
    Route::get('/search', [StorefrontController::class, 'search'])->middleware('throttle:search')->name('search');
    Route::get('/search/suggest', [StorefrontController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');
    Route::post('/analytics/events', [StorefrontController::class, 'recordAnalytics'])->middleware('throttle:analytics')->name('analytics.events');
});

Route::prefix('admin/v1')->middleware(['api.token', 'store.resolve', 'throttle:api.admin'])->name('api.admin.')->group(function (): void {
    Route::get('/stores/{storeId}/me', [AdminController::class, 'me'])->whereNumber('storeId')->middleware('api.token')->name('stores.me');
    Route::get('/stores/{storeId}/products', [AdminController::class, 'products'])->whereNumber('storeId')->middleware('api.token:read-products')->name('products.index');
    Route::post('/stores/{storeId}/products', [AdminController::class, 'createProduct'])->whereNumber('storeId')->middleware('api.token:write-products')->name('products.store');
    Route::get('/stores/{storeId}/products/{productId}', [AdminController::class, 'product'])->whereNumber('storeId')->whereNumber('productId')->middleware('api.token:read-products')->name('products.show');
    Route::put('/stores/{storeId}/products/{productId}', [AdminController::class, 'updateProduct'])->whereNumber('storeId')->whereNumber('productId')->middleware('api.token:write-products')->name('products.update');
    Route::delete('/stores/{storeId}/products/{productId}', [AdminController::class, 'deleteProduct'])->whereNumber('storeId')->whereNumber('productId')->middleware('api.token:write-products')->name('products.destroy');
    Route::post('/stores/{storeId}/products/{productId}/media/presign-upload', [ProductMediaUploadController::class, 'presign'])->whereNumber('storeId')->whereNumber('productId')->middleware('api.token:write-products')->name('products.media.presign');
    Route::get('/stores/{storeId}/collections', [AdminController::class, 'collections'])->whereNumber('storeId')->middleware('api.token:read-collections')->name('collections.index');
    Route::post('/stores/{storeId}/collections', [AdminController::class, 'createCollection'])->whereNumber('storeId')->middleware('api.token:write-collections')->name('collections.store');
    Route::put('/stores/{storeId}/collections/{collectionId}', [AdminController::class, 'updateCollection'])->whereNumber('storeId')->whereNumber('collectionId')->middleware('api.token:write-collections')->name('collections.update');
    Route::delete('/stores/{storeId}/collections/{collectionId}', [AdminController::class, 'deleteCollection'])->whereNumber('storeId')->whereNumber('collectionId')->middleware('api.token:write-collections')->name('collections.destroy');
    Route::get('/stores/{storeId}/orders', [AdminController::class, 'orders'])->whereNumber('storeId')->middleware('api.token:read-orders')->name('orders.index');
    Route::get('/stores/{storeId}/orders/{orderId}', [AdminController::class, 'order'])->whereNumber('storeId')->whereNumber('orderId')->middleware('api.token:read-orders')->name('orders.show');
    Route::post('/stores/{storeId}/orders/{orderId}/fulfillments', [AdminController::class, 'fulfill'])->whereNumber('storeId')->whereNumber('orderId')->middleware('api.token:write-orders')->name('orders.fulfillments.store');
    Route::post('/stores/{storeId}/orders/{orderId}/refunds', [AdminController::class, 'refund'])->whereNumber('storeId')->whereNumber('orderId')->middleware('api.token:write-orders')->name('orders.refunds.store');
    Route::get('/stores/{storeId}/discounts', [AdminController::class, 'discounts'])->whereNumber('storeId')->middleware('api.token:read-discounts')->name('discounts.index');
    Route::post('/stores/{storeId}/discounts', [AdminController::class, 'createDiscount'])->whereNumber('storeId')->middleware('api.token:write-discounts')->name('discounts.store');
    Route::put('/stores/{storeId}/discounts/{discountId}', [AdminController::class, 'updateDiscount'])->whereNumber('storeId')->whereNumber('discountId')->middleware('api.token:write-discounts')->name('discounts.update');
    Route::delete('/stores/{storeId}/discounts/{discountId}', [AdminController::class, 'deleteDiscount'])->whereNumber('storeId')->whereNumber('discountId')->middleware('api.token:write-discounts')->name('discounts.destroy');
    Route::get('/stores/{storeId}/shipping/zones', [StoreConfigurationController::class, 'shippingZones'])->whereNumber('storeId')->middleware('api.token:read-settings')->name('shipping.zones.index');
    Route::post('/stores/{storeId}/shipping/zones', [StoreConfigurationController::class, 'createShippingZone'])->whereNumber('storeId')->middleware('api.token:write-settings')->name('shipping.zones.store');
    Route::put('/stores/{storeId}/shipping/zones/{zoneId}', [StoreConfigurationController::class, 'updateShippingZone'])->whereNumber('storeId')->whereNumber('zoneId')->middleware('api.token:write-settings')->name('shipping.zones.update');
    Route::post('/stores/{storeId}/shipping/zones/{zoneId}/rates', [StoreConfigurationController::class, 'createShippingRate'])->whereNumber('storeId')->whereNumber('zoneId')->middleware('api.token:write-settings')->name('shipping.rates.store');
    Route::get('/stores/{storeId}/tax/settings', [AdminController::class, 'taxSettings'])->whereNumber('storeId')->middleware('api.token:read-settings')->name('tax.settings');
    Route::put('/stores/{storeId}/tax/settings', [AdminController::class, 'updateTaxSettings'])->whereNumber('storeId')->middleware('api.token:write-settings')->name('tax.settings.update');
    Route::post('/stores/{storeId}/themes', [StoreConfigurationController::class, 'createTheme'])->whereNumber('storeId')->middleware('api.token:write-themes')->name('themes.store');
    Route::post('/stores/{storeId}/themes/{themeId}/publish', [StoreConfigurationController::class, 'publishTheme'])->whereNumber('storeId')->whereNumber('themeId')->middleware('api.token:write-themes')->name('themes.publish');
    Route::put('/stores/{storeId}/themes/{themeId}/settings', [StoreConfigurationController::class, 'updateThemeSettings'])->whereNumber('storeId')->whereNumber('themeId')->middleware('api.token:write-themes')->name('themes.settings.update');
    Route::get('/stores/{storeId}/pages', [StoreConfigurationController::class, 'pages'])->whereNumber('storeId')->middleware('api.token:read-content')->name('pages.index');
    Route::post('/stores/{storeId}/pages', [StoreConfigurationController::class, 'createPage'])->whereNumber('storeId')->middleware('api.token:write-content')->name('pages.store');
    Route::put('/stores/{storeId}/pages/{pageId}', [StoreConfigurationController::class, 'updatePage'])->whereNumber('storeId')->whereNumber('pageId')->middleware('api.token:write-content')->name('pages.update');
    Route::delete('/stores/{storeId}/pages/{pageId}', [StoreConfigurationController::class, 'deletePage'])->whereNumber('storeId')->whereNumber('pageId')->middleware('api.token:write-content')->name('pages.destroy');
    Route::post('/stores/{storeId}/search/reindex', [AdminController::class, 'reindexSearch'])->whereNumber('storeId')->middleware('api.token:write-settings')->name('search.reindex');
    Route::get('/stores/{storeId}/search/status', [AdminController::class, 'searchStatus'])->whereNumber('storeId')->middleware('api.token:read-settings')->name('search.status');
    Route::get('/stores/{storeId}/analytics/summary', [AdminController::class, 'analyticsSummary'])->whereNumber('storeId')->middleware('api.token:read-analytics')->name('analytics.summary');
    Route::post('/stores/{storeId}/exports/orders', [OrderExportController::class, 'store'])->whereNumber('storeId')->middleware('api.token:read-orders')->name('exports.orders.store');
    Route::get('/stores/{storeId}/exports/{exportId}', [OrderExportController::class, 'show'])->whereNumber('storeId')->whereNumber('exportId')->middleware('api.token:read-orders')->name('exports.show');
});

Route::get('/admin/v1/stores/{storeId}/exports/{exportId}/download', [OrderExportController::class, 'download'])
    ->whereNumber('storeId')
    ->whereNumber('exportId')
    ->middleware('signed')
    ->name('api.admin.exports.download');

Route::put('/uploads/product-media/{mediaId}', [ProductMediaUploadController::class, 'upload'])
    ->whereNumber('mediaId')
    ->middleware('signed')
    ->name('api.product-media.upload');

Route::prefix('apps/v1/stores/{storeId}')->whereNumber('storeId')->name('api.apps.')->group(function (): void {
    Route::get('/products', fn () => response()->json(['message' => 'The app ecosystem is not implemented.'], 501))->name('products.index');
    Route::get('/orders', fn () => response()->json(['message' => 'The app ecosystem is not implemented.'], 501))->name('orders.index');
    Route::get('/customers', fn () => response()->json(['message' => 'The app ecosystem is not implemented.'], 501))->name('customers.index');
});
