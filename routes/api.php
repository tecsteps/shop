<?php

use App\Http\Controllers\Api\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\WebhookController as AdminWebhookController;
use App\Http\Controllers\Api\Storefront\AnalyticsEventsController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController as StorefrontOrderController;
use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', fn ($request) => $request->user());

Route::prefix('storefront/v1')
    ->middleware('store.resolve:storefront')
    ->group(function (): void {
        Route::post('carts', [CartController::class, 'store']);
        Route::get('carts/{cart}', [CartController::class, 'show']);
        Route::post('carts/{cart}/lines', [CartController::class, 'addLine']);
        Route::put('carts/{cart}/lines/{line}', [CartController::class, 'updateLine']);
        Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'removeLine']);

        Route::post('checkouts', [CheckoutController::class, 'store']);
        Route::get('checkouts/{checkout}', [CheckoutController::class, 'show']);
        Route::put('checkouts/{checkout}/address', [CheckoutController::class, 'setAddress']);
        Route::put('checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
        Route::post('checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::delete('checkouts/{checkout}/discount', [CheckoutController::class, 'removeDiscount']);
        Route::post('checkouts/{checkout}/pay', [CheckoutController::class, 'pay']);

        Route::get('orders/{orderNumber}', [StorefrontOrderController::class, 'show'])
            ->where('orderNumber', '[%23#\d]+');

        Route::get('search', SearchController::class);
        Route::post('analytics/events', [AnalyticsEventsController::class, 'store']);
    });

Route::prefix('admin/v1')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('stores/{storeId}/products', [AdminProductController::class, 'index']);
        Route::post('stores/{storeId}/products', [AdminProductController::class, 'store']);
        Route::get('stores/{storeId}/products/{productId}', [AdminProductController::class, 'show']);
        Route::put('stores/{storeId}/products/{productId}', [AdminProductController::class, 'update']);
        Route::delete('stores/{storeId}/products/{productId}', [AdminProductController::class, 'destroy']);

        Route::get('stores/{storeId}/collections', [AdminCollectionController::class, 'index']);
        Route::get('stores/{storeId}/collections/{collectionId}', [AdminCollectionController::class, 'show']);

        Route::get('stores/{storeId}/orders', [AdminOrderController::class, 'index']);
        Route::get('stores/{storeId}/orders/{orderId}', [AdminOrderController::class, 'show']);

        Route::get('stores/{storeId}/customers', [AdminCustomerController::class, 'index']);
        Route::get('stores/{storeId}/customers/{customerId}', [AdminCustomerController::class, 'show']);

        Route::get('stores/{storeId}/webhook-subscriptions', [AdminWebhookController::class, 'index']);
        Route::post('stores/{storeId}/webhook-subscriptions', [AdminWebhookController::class, 'store']);
        Route::delete('stores/{storeId}/webhook-subscriptions/{subscriptionId}', [AdminWebhookController::class, 'destroy']);
    });
