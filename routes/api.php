<?php

use App\Http\Controllers\Api\Admin\V1\AnalyticsSummaryController as AdminAnalyticsSummaryController;
use App\Http\Controllers\Api\Admin\V1\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\V1\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\V1\DiscountController as AdminDiscountController;
use App\Http\Controllers\Api\Admin\V1\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\V1\OrderExportController as AdminOrderExportController;
use App\Http\Controllers\Api\Admin\V1\OrderFulfillmentController as AdminOrderFulfillmentController;
use App\Http\Controllers\Api\Admin\V1\OrderRefundController as AdminOrderRefundController;
use App\Http\Controllers\Api\Admin\V1\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\V1\PlatformOrganizationController as AdminPlatformOrganizationController;
use App\Http\Controllers\Api\Admin\V1\PlatformStoreController as AdminPlatformStoreController;
use App\Http\Controllers\Api\Admin\V1\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\V1\SearchIndexController as AdminSearchIndexController;
use App\Http\Controllers\Api\Admin\V1\ShippingRateController as AdminShippingRateController;
use App\Http\Controllers\Api\Admin\V1\ShippingZoneController as AdminShippingZoneController;
use App\Http\Controllers\Api\Admin\V1\StoreInviteController as AdminStoreInviteController;
use App\Http\Controllers\Api\Admin\V1\StoreMembershipController as AdminStoreMembershipController;
use App\Http\Controllers\Api\Admin\V1\StoreSettingsController as AdminStoreSettingsController;
use App\Http\Controllers\Api\Admin\V1\TaxSettingsController as AdminTaxSettingsController;
use App\Http\Controllers\Api\Admin\V1\ThemeController as AdminThemeController;
use App\Http\Controllers\Api\Admin\V1\ThemeSettingsController as AdminThemeSettingsController;
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
    ->prefix('admin/v1/platform')
    ->name('api.admin.v1.platform.')
    ->middleware('platform.api')
    ->group(function (): void {
        Route::post('organizations', [AdminPlatformOrganizationController::class, 'store'])->name('organizations.store');
        Route::post('stores', [AdminPlatformStoreController::class, 'store'])->name('stores.store');
    });

Route::middleware('throttle:60,1')
    ->prefix('admin/v1/stores/{store}')
    ->name('api.admin.v1.')
    ->group(function (): void {
        Route::middleware('admin.api')->group(function (): void {
            Route::get('me', [AdminStoreMembershipController::class, 'show'])->name('stores.me');
        });

        Route::middleware('admin.api:manage-platform')->group(function (): void {
            Route::post('invites', [AdminStoreInviteController::class, 'store'])->name('stores.invites.store');
        });

        Route::middleware('admin.api:read-products')->group(function (): void {
            Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
            Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        });

        Route::middleware('admin.api:write-products')->group(function (): void {
            Route::post('products', [AdminProductController::class, 'store'])->name('products.store');
            Route::put('products/{product}', [AdminProductController::class, 'update'])->name('products.update');
            Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
            Route::post('products/{product}/media/presign-upload', [AdminProductController::class, 'presignUpload'])->name('products.media.presign-upload');
        });

        Route::middleware('admin.api:read-customers')->group(function (): void {
            Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        });

        Route::middleware('admin.api:read-collections')->group(function (): void {
            Route::get('collections', [AdminCollectionController::class, 'index'])->name('collections.index');
        });

        Route::middleware('admin.api:write-collections')->group(function (): void {
            Route::post('collections', [AdminCollectionController::class, 'store'])->name('collections.store');
            Route::put('collections/{collection}', [AdminCollectionController::class, 'update'])->name('collections.update');
            Route::delete('collections/{collection}', [AdminCollectionController::class, 'destroy'])->name('collections.destroy');
        });

        Route::middleware('admin.api:read-discounts')->group(function (): void {
            Route::get('discounts', [AdminDiscountController::class, 'index'])->name('discounts.index');
        });

        Route::middleware('admin.api:write-discounts')->group(function (): void {
            Route::post('discounts', [AdminDiscountController::class, 'store'])->name('discounts.store');
            Route::put('discounts/{discount}', [AdminDiscountController::class, 'update'])->name('discounts.update');
            Route::delete('discounts/{discount}', [AdminDiscountController::class, 'destroy'])->name('discounts.destroy');
        });

        Route::middleware('admin.api:read-content')->group(function (): void {
            Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
        });

        Route::middleware('admin.api:write-content')->group(function (): void {
            Route::post('pages', [AdminPageController::class, 'store'])->name('pages.store');
            Route::put('pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
            Route::delete('pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');
        });

        Route::middleware('admin.api:read-settings')->group(function (): void {
            Route::get('search/status', [AdminSearchIndexController::class, 'status'])->name('search.status');
            Route::get('settings', [AdminStoreSettingsController::class, 'show'])->name('settings.show');
            Route::get('shipping/zones', [AdminShippingZoneController::class, 'index'])->name('shipping.zones.index');
            Route::get('tax/settings', [AdminTaxSettingsController::class, 'show'])->name('tax.settings.show');
        });

        Route::middleware('admin.api:write-settings')->group(function (): void {
            Route::post('search/reindex', [AdminSearchIndexController::class, 'reindex'])->name('search.reindex');
            Route::put('settings', [AdminStoreSettingsController::class, 'update'])->name('settings.update');
            Route::post('shipping/zones', [AdminShippingZoneController::class, 'store'])->name('shipping.zones.store');
            Route::put('shipping/zones/{shippingZone}', [AdminShippingZoneController::class, 'update'])->name('shipping.zones.update');
            Route::post('shipping/zones/{shippingZone}/rates', [AdminShippingRateController::class, 'store'])->name('shipping.zones.rates.store');
            Route::put('tax/settings', [AdminTaxSettingsController::class, 'update'])->name('tax.settings.update');
        });

        Route::middleware('admin.api:write-themes')->group(function (): void {
            Route::post('themes', [AdminThemeController::class, 'store'])->name('themes.store');
            Route::post('themes/{theme}/publish', [AdminThemeController::class, 'publish'])->name('themes.publish');
            Route::put('themes/{theme}/settings', [AdminThemeSettingsController::class, 'update'])->name('themes.settings.update');
        });

        Route::middleware('admin.api:read-analytics')->group(function (): void {
            Route::get('analytics/summary', [AdminAnalyticsSummaryController::class, 'show'])->name('analytics.summary');
        });

        Route::middleware('admin.api:read-orders')->group(function (): void {
            Route::post('exports/orders', [AdminOrderExportController::class, 'store'])->name('exports.orders.store');
            Route::get('exports/{dataExport}', [AdminOrderExportController::class, 'show'])->name('exports.show');
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
