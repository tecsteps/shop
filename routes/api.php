<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')->middleware(['store.resolve', 'throttle:storefront-api'])->name('api.storefront.')->group(function (): void {
    Route::post('/carts', [StorefrontController::class, 'createCart'])->name('carts.create');
    Route::get('/carts/{cartId}', [StorefrontController::class, 'showCart'])->whereNumber('cartId')->name('carts.show');
    Route::post('/carts/{cartId}/lines', [StorefrontController::class, 'addCartLine'])->whereNumber('cartId')->name('carts.lines.add');
    Route::put('/carts/{cartId}/lines/{lineId}', [StorefrontController::class, 'updateCartLine'])->whereNumber('cartId')->whereNumber('lineId')->name('carts.lines.update');
    Route::delete('/carts/{cartId}/lines/{lineId}', [StorefrontController::class, 'deleteCartLine'])->whereNumber('cartId')->whereNumber('lineId')->name('carts.lines.delete');
    Route::post('/checkouts', [StorefrontController::class, 'createCheckout'])->name('checkouts.create');
    Route::get('/checkouts/{checkoutId}', [StorefrontController::class, 'showCheckout'])->whereNumber('checkoutId')->name('checkouts.show');
    Route::put('/checkouts/{checkoutId}/address', [StorefrontController::class, 'updateAddress'])->whereNumber('checkoutId')->name('checkouts.address');
    Route::put('/checkouts/{checkoutId}/shipping-method', [StorefrontController::class, 'updateShipping'])->whereNumber('checkoutId')->name('checkouts.shipping');
    Route::put('/checkouts/{checkoutId}/payment-method', [StorefrontController::class, 'updatePayment'])->whereNumber('checkoutId')->name('checkouts.payment');
    Route::post('/checkouts/{checkoutId}/apply-discount', [StorefrontController::class, 'applyDiscount'])->whereNumber('checkoutId')->name('checkouts.discount.apply');
    Route::delete('/checkouts/{checkoutId}/discount', [StorefrontController::class, 'removeDiscount'])->whereNumber('checkoutId')->name('checkouts.discount.remove');
    Route::post('/checkouts/{checkoutId}/pay', [StorefrontController::class, 'pay'])->whereNumber('checkoutId')->middleware('throttle:checkout')->name('checkouts.pay');
    Route::get('/orders/{orderNumber}', [StorefrontController::class, 'showOrder'])->name('orders.show');
    Route::get('/search', [StorefrontController::class, 'search'])->middleware('throttle:search')->name('search');
    Route::get('/search/suggest', [StorefrontController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');
    Route::post('/analytics/events', [StorefrontController::class, 'recordAnalytics'])->middleware('throttle:analytics')->name('analytics.events');
});

Route::prefix('admin/v1')->middleware(['api.token', 'store.resolve', 'throttle:admin-api'])->name('api.admin.')->group(function (): void {
    Route::get('/stores/{storeId}/me', [AdminController::class, 'me'])->whereNumber('storeId')->name('stores.me');
    Route::get('/stores/{storeId}/products', [AdminController::class, 'products'])->whereNumber('storeId')->name('products.index');
    Route::post('/stores/{storeId}/products', [AdminController::class, 'createProduct'])->whereNumber('storeId')->name('products.store');
    Route::get('/stores/{storeId}/products/{productId}', [AdminController::class, 'product'])->whereNumber('storeId')->whereNumber('productId')->name('products.show');
    Route::put('/stores/{storeId}/products/{productId}', [AdminController::class, 'updateProduct'])->whereNumber('storeId')->whereNumber('productId')->name('products.update');
    Route::delete('/stores/{storeId}/products/{productId}', [AdminController::class, 'deleteProduct'])->whereNumber('storeId')->whereNumber('productId')->name('products.destroy');
    Route::get('/stores/{storeId}/collections', [AdminController::class, 'collections'])->whereNumber('storeId')->name('collections.index');
    Route::get('/stores/{storeId}/orders', [AdminController::class, 'orders'])->whereNumber('storeId')->name('orders.index');
    Route::get('/stores/{storeId}/orders/{orderId}', [AdminController::class, 'order'])->whereNumber('storeId')->whereNumber('orderId')->name('orders.show');
    Route::post('/stores/{storeId}/orders/{orderId}/fulfillments', [AdminController::class, 'fulfill'])->whereNumber('storeId')->whereNumber('orderId')->name('orders.fulfillments.store');
    Route::post('/stores/{storeId}/orders/{orderId}/refunds', [AdminController::class, 'refund'])->whereNumber('storeId')->whereNumber('orderId')->name('orders.refunds.store');
    Route::get('/stores/{storeId}/discounts', [AdminController::class, 'discounts'])->whereNumber('storeId')->name('discounts.index');
    Route::get('/stores/{storeId}/shipping/zones', [AdminController::class, 'shippingZones'])->whereNumber('storeId')->name('shipping.zones.index');
    Route::get('/stores/{storeId}/tax/settings', [AdminController::class, 'taxSettings'])->whereNumber('storeId')->name('tax.settings');
    Route::put('/stores/{storeId}/tax/settings', [AdminController::class, 'updateTaxSettings'])->whereNumber('storeId')->name('tax.settings.update');
    Route::get('/stores/{storeId}/pages', [AdminController::class, 'pages'])->whereNumber('storeId')->name('pages.index');
    Route::post('/stores/{storeId}/search/reindex', [AdminController::class, 'reindexSearch'])->whereNumber('storeId')->name('search.reindex');
    Route::get('/stores/{storeId}/search/status', [AdminController::class, 'searchStatus'])->whereNumber('storeId')->name('search.status');
    Route::get('/stores/{storeId}/analytics/summary', [AdminController::class, 'analyticsSummary'])->whereNumber('storeId')->name('analytics.summary');
});
