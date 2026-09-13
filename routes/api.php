<?php

use App\Http\Controllers\Api\Admin\V1\AnalyticsController;
use App\Http\Controllers\Api\Admin\V1\CollectionController;
use App\Http\Controllers\Api\Admin\V1\DiscountController;
use App\Http\Controllers\Api\Admin\V1\FulfillmentController;
use App\Http\Controllers\Api\Admin\V1\MeController;
use App\Http\Controllers\Api\Admin\V1\OrderController;
use App\Http\Controllers\Api\Admin\V1\Platform\OrganizationController;
use App\Http\Controllers\Api\Admin\V1\Platform\StoreController as PlatformStoreController;
use App\Http\Controllers\Api\Admin\V1\ProductController;
use App\Http\Controllers\Api\Admin\V1\ProductMediaController;
use App\Http\Controllers\Api\Admin\V1\RefundController;
use App\Http\Controllers\Api\Admin\V1\SearchAdminController;
use App\Http\Controllers\Api\Admin\V1\ShippingRateController;
use App\Http\Controllers\Api\Admin\V1\ShippingZoneController;
use App\Http\Controllers\Api\Admin\V1\StoreInviteController;
use App\Http\Controllers\Api\Admin\V1\TaxSettingController;
use App\Http\Controllers\Api\Admin\V1\TokenController;
use App\Http\Controllers\Api\Admin\V1\VariantInventoryController;
use App\Http\Controllers\Api\OauthStubController;
use App\Http\Controllers\Api\Storefront\V1\AnalyticsEventController;
use App\Http\Controllers\Api\Storefront\V1\CartController;
use App\Http\Controllers\Api\Storefront\V1\CheckoutController;
use App\Http\Controllers\Api\Storefront\V1\OrderController as StorefrontOrderController;
use App\Http\Controllers\Api\Storefront\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/v1')->name('api.admin.')->group(function (): void {
    Route::middleware(['auth:sanctum', 'store.resolve', 'throttle:api.admin'])->group(function (): void {
        Route::post('platform/organizations', [OrganizationController::class, 'store'])
            ->middleware('abilities:manage-platform')
            ->name('platform.organizations.store');

        Route::post('platform/stores', [PlatformStoreController::class, 'store'])
            ->middleware('abilities:manage-platform')
            ->name('platform.stores.store');

        Route::post('stores/{storeId}/invites', [StoreInviteController::class, 'store'])
            ->middleware('abilities:manage-platform')
            ->name('stores.invites.store');

        Route::get('stores/{storeId}/me', [MeController::class, 'show'])->name('stores.me');

        Route::get('stores/{storeId}/products', [ProductController::class, 'index'])
            ->middleware('abilities:read-products')
            ->name('stores.products.index');

        Route::post('stores/{storeId}/products', [ProductController::class, 'store'])
            ->middleware('abilities:write-products')
            ->name('stores.products.store');

        Route::get('stores/{storeId}/products/{productId}', [ProductController::class, 'show'])
            ->middleware('abilities:read-products')
            ->name('stores.products.show');

        Route::put('stores/{storeId}/products/{productId}', [ProductController::class, 'update'])
            ->middleware('abilities:write-products')
            ->name('stores.products.update');

        Route::delete('stores/{storeId}/products/{productId}', [ProductController::class, 'destroy'])
            ->middleware('abilities:write-products')
            ->name('stores.products.destroy');

        Route::get('stores/{storeId}/products/{productId}/variants/{variantId}/inventory', [VariantInventoryController::class, 'show'])
            ->middleware('abilities:read-products')
            ->name('stores.products.variants.inventory.show');

        Route::put('stores/{storeId}/products/{productId}/variants/{variantId}/inventory', [VariantInventoryController::class, 'update'])
            ->middleware('abilities:write-products')
            ->name('stores.products.variants.inventory.update');

        Route::post('stores/{storeId}/products/{productId}/media', [ProductMediaController::class, 'store'])
            ->middleware('abilities:write-products')
            ->name('stores.products.media.store');

        Route::put('stores/{storeId}/products/{productId}/media/{mediaId}', [ProductMediaController::class, 'update'])
            ->middleware('abilities:write-products')
            ->name('stores.products.media.update');

        Route::delete('stores/{storeId}/products/{productId}/media/{mediaId}', [ProductMediaController::class, 'destroy'])
            ->middleware('abilities:write-products')
            ->name('stores.products.media.destroy');

        Route::get('stores/{storeId}/collections', [CollectionController::class, 'index'])
            ->middleware('abilities:read-collections')
            ->name('stores.collections.index');

        Route::post('stores/{storeId}/collections', [CollectionController::class, 'store'])
            ->middleware('abilities:write-collections')
            ->name('stores.collections.store');

        Route::get('stores/{storeId}/collections/{collectionId}', [CollectionController::class, 'show'])
            ->middleware('abilities:read-collections')
            ->name('stores.collections.show');

        Route::put('stores/{storeId}/collections/{collectionId}', [CollectionController::class, 'update'])
            ->middleware('abilities:write-collections')
            ->name('stores.collections.update');

        Route::delete('stores/{storeId}/collections/{collectionId}', [CollectionController::class, 'destroy'])
            ->middleware('abilities:write-collections')
            ->name('stores.collections.destroy');

        Route::get('stores/{storeId}/orders', [OrderController::class, 'index'])
            ->middleware('abilities:read-orders')
            ->name('stores.orders.index');

        Route::get('stores/{storeId}/orders/{orderId}', [OrderController::class, 'show'])
            ->middleware('abilities:read-orders')
            ->name('stores.orders.show');

        Route::post('stores/{storeId}/orders/{orderId}/confirm-payment', [OrderController::class, 'confirmPayment'])
            ->middleware('abilities:write-orders')
            ->name('stores.orders.confirm-payment');

        Route::post('stores/{storeId}/orders/{orderId}/fulfillments', [FulfillmentController::class, 'store'])
            ->middleware('abilities:write-orders')
            ->name('stores.orders.fulfillments.store');

        Route::post('stores/{storeId}/orders/{orderId}/fulfillments/{fulfillmentId}/ship', [FulfillmentController::class, 'ship'])
            ->middleware('abilities:write-orders')
            ->name('stores.orders.fulfillments.ship');

        Route::post('stores/{storeId}/orders/{orderId}/fulfillments/{fulfillmentId}/deliver', [FulfillmentController::class, 'deliver'])
            ->middleware('abilities:write-orders')
            ->name('stores.orders.fulfillments.deliver');

        Route::get('stores/{storeId}/orders/{orderId}/refunds', [RefundController::class, 'index'])
            ->middleware('abilities:read-orders')
            ->name('stores.orders.refunds.index');

        Route::post('stores/{storeId}/orders/{orderId}/refunds', [RefundController::class, 'store'])
            ->middleware('abilities:write-orders')
            ->name('stores.orders.refunds.store');

        Route::get('stores/{storeId}/discounts', [DiscountController::class, 'index'])
            ->middleware('abilities:read-discounts')
            ->name('stores.discounts.index');

        Route::post('stores/{storeId}/discounts', [DiscountController::class, 'store'])
            ->middleware('abilities:write-discounts')
            ->name('stores.discounts.store');

        Route::get('stores/{storeId}/discounts/{discountId}', [DiscountController::class, 'show'])
            ->middleware('abilities:read-discounts')
            ->name('stores.discounts.show');

        Route::put('stores/{storeId}/discounts/{discountId}', [DiscountController::class, 'update'])
            ->middleware('abilities:write-discounts')
            ->name('stores.discounts.update');

        Route::delete('stores/{storeId}/discounts/{discountId}', [DiscountController::class, 'destroy'])
            ->middleware('abilities:write-discounts')
            ->name('stores.discounts.destroy');

        Route::get('stores/{storeId}/shipping/zones', [ShippingZoneController::class, 'index'])
            ->middleware('abilities:read-settings')
            ->name('stores.shipping.zones.index');

        Route::post('stores/{storeId}/shipping/zones', [ShippingZoneController::class, 'store'])
            ->middleware('abilities:write-settings')
            ->name('stores.shipping.zones.store');

        Route::put('stores/{storeId}/shipping/zones/{zoneId}', [ShippingZoneController::class, 'update'])
            ->middleware('abilities:write-settings')
            ->name('stores.shipping.zones.update');

        Route::delete('stores/{storeId}/shipping/zones/{zoneId}', [ShippingZoneController::class, 'destroy'])
            ->middleware('abilities:write-settings')
            ->name('stores.shipping.zones.destroy');

        Route::post('stores/{storeId}/shipping/zones/{zoneId}/rates', [ShippingRateController::class, 'store'])
            ->middleware('abilities:write-settings')
            ->name('stores.shipping.zones.rates.store');

        Route::delete('stores/{storeId}/shipping/zones/{zoneId}/rates/{rateId}', [ShippingRateController::class, 'destroy'])
            ->middleware('abilities:write-settings')
            ->name('stores.shipping.zones.rates.destroy');

        Route::get('stores/{storeId}/tax/settings', [TaxSettingController::class, 'show'])
            ->middleware('abilities:read-settings')
            ->name('stores.tax.settings.show');

        Route::put('stores/{storeId}/tax/settings', [TaxSettingController::class, 'update'])
            ->middleware('abilities:write-settings')
            ->name('stores.tax.settings.update');

        Route::post('stores/{storeId}/search/reindex', [SearchAdminController::class, 'reindex'])
            ->middleware('abilities:write-settings')
            ->name('stores.search.reindex');

        Route::get('stores/{storeId}/search/status', [SearchAdminController::class, 'status'])
            ->middleware('abilities:read-settings')
            ->name('stores.search.status');

        Route::get('stores/{storeId}/analytics/summary', [AnalyticsController::class, 'summary'])
            ->middleware('abilities:read-analytics')
            ->name('stores.analytics.summary');
    });

    Route::middleware(['web', 'auth', 'throttle:api.admin'])->group(function (): void {
        Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
        Route::delete('tokens/{tokenId}', [TokenController::class, 'destroy'])->name('tokens.destroy');
    });
});

Route::prefix('storefront/v1')->name('api.storefront.')->middleware(['store.resolve', 'throttle:search'])->group(function (): void {
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
    Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
});

Route::prefix('storefront/v1')->name('api.storefront.')->middleware(['store.resolve', 'throttle:analytics'])->group(function (): void {
    Route::post('analytics/events', [AnalyticsEventController::class, 'store'])->name('analytics.events.store');
});

Route::prefix('storefront/v1')->name('api.storefront.')->middleware(['store.resolve', 'throttle:api.storefront'])->group(function (): void {
    Route::post('carts', [CartController::class, 'store'])->name('carts.store');
    Route::get('carts/{cartId}', [CartController::class, 'show'])->name('carts.show');
    Route::post('carts/{cartId}/lines', [CartController::class, 'addLine'])->name('carts.lines.store');
    Route::put('carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine'])->name('carts.lines.update');
    Route::delete('carts/{cartId}/lines/{lineId}', [CartController::class, 'removeLine'])->name('carts.lines.destroy');

    Route::get('orders/{orderNumber}', [StorefrontOrderController::class, 'show'])->name('orders.show');
});

Route::prefix('storefront/v1')->name('api.storefront.')->middleware(['store.resolve', 'throttle:checkout'])->group(function (): void {
    Route::post('checkouts', [CheckoutController::class, 'store'])->name('checkouts.store');
    Route::get('checkouts/{checkoutId}', [CheckoutController::class, 'show'])->name('checkouts.show');
    Route::put('checkouts/{checkoutId}/address', [CheckoutController::class, 'setAddress'])->name('checkouts.address');
    Route::put('checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'setShippingMethod'])->name('checkouts.shipping-method');
    Route::put('checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'selectPaymentMethod'])->name('checkouts.payment-method');
    Route::post('checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('checkouts.apply-discount');
    Route::delete('checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount'])->name('checkouts.remove-discount');
    Route::post('checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay'])->name('checkouts.pay');
});

Route::prefix('apps/v1')->name('api.apps.')->group(function (): void {
    Route::get('stores/{storeId}/products', [OauthStubController::class, 'appResource'])->name('stores.products.index');
    Route::get('stores/{storeId}/orders', [OauthStubController::class, 'appResource'])->name('stores.orders.index');
    Route::get('stores/{storeId}/customers', [OauthStubController::class, 'appResource'])->name('stores.customers.index');
});
