<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\StorefrontAnalyticsController;
use App\Http\Controllers\Api\StorefrontCartController;
use App\Http\Controllers\Api\StorefrontCheckoutController;
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
    Route::post('checkouts/{checkoutId}/pay', [StorefrontCheckoutController::class, 'pay'])->middleware('throttle:checkout');
    Route::post('analytics/events', [StorefrontAnalyticsController::class, 'store']);
});

Route::prefix('admin/v1/stores/{storeId}')->middleware([StartSession::class, 'auth', 'store.resolve', 'role.check:owner,admin,staff,support', 'throttle:api.admin'])->group(function (): void {
    Route::get('products', [AdminController::class, 'products']);
    Route::post('products', [AdminController::class, 'storeProduct'])->middleware('role.check:owner,admin,staff');
    Route::get('products/{productId}', [AdminController::class, 'showProduct']);
    Route::put('products/{productId}', [AdminController::class, 'updateProduct'])->middleware('role.check:owner,admin,staff');
    Route::delete('products/{productId}', [AdminController::class, 'deleteProduct'])->middleware('role.check:owner,admin,staff');
    Route::get('collections', [AdminController::class, 'collections']);
    Route::post('collections', [AdminController::class, 'storeCollection'])->middleware('role.check:owner,admin,staff');
    Route::put('collections/{collectionId}', [AdminController::class, 'updateCollection'])->middleware('role.check:owner,admin,staff');
    Route::delete('collections/{collectionId}', [AdminController::class, 'deleteCollection'])->middleware('role.check:owner,admin,staff');
    Route::get('orders', [AdminController::class, 'orders']);
    Route::get('orders/{orderId}', [AdminController::class, 'showOrder']);
    Route::get('customers', [AdminController::class, 'customers']);
    Route::get('discounts', [AdminController::class, 'discounts']);
});
