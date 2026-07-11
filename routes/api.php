<?php

use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\Api\Admin\ExportController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\PlatformController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Api\Admin\ShippingZoneController;
use App\Http\Controllers\Api\Admin\TaxSettingsController;
use App\Http\Controllers\Api\Admin\ThemeController;
use App\Http\Controllers\Api\Storefront\AnalyticsController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController;
use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')->name('api.storefront.')->middleware(['store.resolve', 'throttle:api.storefront'])->group(function (): void {
    Route::post('carts', [CartController::class, 'store'])->name('carts.store');
    Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
    Route::post('carts/{cart}/lines', [CartController::class, 'addLine'])->name('carts.lines.store');
    Route::put('carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->name('carts.lines.update');
    Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'destroyLine'])->name('carts.lines.destroy');

    Route::middleware('throttle:checkout')->group(function (): void {
        Route::post('checkouts', [CheckoutController::class, 'store'])->name('checkouts.store');
        Route::get('checkouts/{checkout}', [CheckoutController::class, 'show'])->name('checkouts.show');
        Route::put('checkouts/{checkout}/address', [CheckoutController::class, 'address'])->name('checkouts.address');
        Route::put('checkouts/{checkout}/shipping-method', [CheckoutController::class, 'shipping'])->name('checkouts.shipping');
        Route::put('checkouts/{checkout}/payment-method', [CheckoutController::class, 'paymentMethod'])->name('checkouts.payment_method');
        Route::post('checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('checkouts.discount.store');
        Route::delete('checkouts/{checkout}/discount', [CheckoutController::class, 'removeDiscount'])->name('checkouts.discount.destroy');
        Route::post('checkouts/{checkout}/pay', [CheckoutController::class, 'pay'])->name('checkouts.pay');
    });

    Route::get('orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('search', [SearchController::class, 'index'])->middleware('throttle:search')->name('search');
    Route::get('search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');
    Route::post('analytics/events', [AnalyticsController::class, 'store'])->middleware('throttle:analytics')->name('analytics.store');
});

Route::prefix('admin/v1')->name('api.admin.')->middleware(['auth:sanctum', 'throttle:api.admin'])->group(function (): void {
    Route::post('platform/organizations', [PlatformController::class, 'organization'])->middleware('abilities:manage-platform')->name('platform.organizations.store');
    Route::post('platform/stores', [PlatformController::class, 'store'])->middleware('abilities:manage-platform')->name('platform.stores.store');

    Route::prefix('stores/{store}')->middleware('store.resolve')->scopeBindings()->group(function (): void {
        Route::post('invites', [PlatformController::class, 'invite'])->middleware('abilities:write-settings')->name('stores.invites.store');
        Route::get('me', [PlatformController::class, 'me'])->name('stores.me');

        Route::get('products', [AdminProductController::class, 'index'])->middleware('abilities:read-products')->name('products.index');
        Route::post('products', [AdminProductController::class, 'store'])->middleware('abilities:write-products')->name('products.store');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->middleware('abilities:read-products')->name('products.show');
        Route::put('products/{product}', [AdminProductController::class, 'update'])->middleware('abilities:write-products')->name('products.update');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('abilities:write-products')->name('products.destroy');
        Route::post('products/{product}/media/presign-upload', fn () => response()->json(['message' => 'Direct uploads use the local public disk.'], 501))->middleware('abilities:write-products')->name('products.media.presign');

        Route::get('collections', [AdminCollectionController::class, 'index'])->middleware('abilities:read-collections')->name('collections.index');
        Route::post('collections', [AdminCollectionController::class, 'store'])->middleware('abilities:write-collections')->name('collections.store');
        Route::put('collections/{collection}', [AdminCollectionController::class, 'update'])->middleware('abilities:write-collections')->name('collections.update');
        Route::delete('collections/{collection}', [AdminCollectionController::class, 'destroy'])->middleware('abilities:write-collections')->name('collections.destroy');

        Route::get('orders', [AdminOrderController::class, 'index'])->middleware('abilities:read-orders')->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->middleware('abilities:read-orders')->name('orders.show');
        Route::post('orders/{order}/fulfillments', [AdminOrderController::class, 'fulfill'])->middleware('abilities:write-orders')->name('orders.fulfillments.store');
        Route::post('orders/{order}/refunds', [AdminOrderController::class, 'refund'])->middleware('abilities:write-orders')->name('orders.refunds.store');
        Route::post('orders/{order}/confirm-payment', [AdminOrderController::class, 'confirmPayment'])->middleware('abilities:write-orders')->name('orders.confirm_payment');

        Route::get('discounts', [AdminDiscountController::class, 'index'])->middleware('abilities:read-discounts')->name('discounts.index');
        Route::post('discounts', [AdminDiscountController::class, 'store'])->middleware('abilities:write-discounts')->name('discounts.store');
        Route::put('discounts/{discount}', [AdminDiscountController::class, 'update'])->middleware('abilities:write-discounts')->name('discounts.update');
        Route::delete('discounts/{discount}', [AdminDiscountController::class, 'destroy'])->middleware('abilities:write-discounts')->name('discounts.destroy');

        Route::get('shipping/zones', [ShippingZoneController::class, 'index'])->middleware('abilities:read-settings')->name('shipping.index');
        Route::post('shipping/zones', [ShippingZoneController::class, 'store'])->middleware('abilities:write-settings')->name('shipping.store');
        Route::put('shipping/zones/{zone}', [ShippingZoneController::class, 'update'])->middleware('abilities:write-settings')->name('shipping.update');
        Route::post('shipping/zones/{zone}/rates', [ShippingZoneController::class, 'storeRate'])->middleware('abilities:write-settings')->name('shipping.rates.store');
        Route::get('tax/settings', [TaxSettingsController::class, 'show'])->middleware('abilities:read-settings')->name('tax.show');
        Route::put('tax/settings', [TaxSettingsController::class, 'update'])->middleware('abilities:write-settings')->name('tax.update');

        Route::post('themes', [ThemeController::class, 'store'])->middleware('abilities:write-themes')->name('themes.store');
        Route::post('themes/{theme}/publish', [ThemeController::class, 'publish'])->middleware('abilities:write-themes')->name('themes.publish');
        Route::put('themes/{theme}/settings', [ThemeController::class, 'updateSettings'])->middleware('abilities:write-themes')->name('themes.settings.update');
        Route::get('pages', [AdminPageController::class, 'index'])->middleware('abilities:read-content')->name('pages.index');
        Route::post('pages', [AdminPageController::class, 'store'])->middleware('abilities:write-content')->name('pages.store');
        Route::put('pages/{page}', [AdminPageController::class, 'update'])->middleware('abilities:write-content')->name('pages.update');
        Route::delete('pages/{page}', [AdminPageController::class, 'destroy'])->middleware('abilities:write-content')->name('pages.destroy');

        Route::post('search/reindex', [AdminSearchController::class, 'reindex'])->middleware('abilities:write-settings')->name('search.reindex');
        Route::get('search/status', [AdminSearchController::class, 'status'])->middleware('abilities:read-settings')->name('search.status');
        Route::get('analytics/summary', [AdminAnalyticsController::class, 'summary'])->middleware('abilities:read-analytics')->name('analytics.summary');
        Route::post('exports/orders', [ExportController::class, 'orders'])->middleware('abilities:read-orders')->name('exports.orders');
    });
});

Route::prefix('app/v1')->middleware('auth:sanctum')->group(function (): void {
    Route::any('{path}', fn () => response()->json(['message' => 'OAuth app APIs are not implemented in this self-contained edition.'], 501))->where('path', '.*');
});
