<?php

use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')
    ->middleware(['store.resolve', 'throttle:api.storefront'])
    ->group(function (): void {
        Route::post('/carts', [CartController::class, 'store'])->name('api.storefront.carts.store');
        Route::get('/carts/{cartId}', [CartController::class, 'show'])->name('api.storefront.carts.show');
        Route::post('/carts/{cartId}/lines', [CartController::class, 'storeLine'])->name('api.storefront.carts.lines.store');
        Route::put('/carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine'])->name('api.storefront.carts.lines.update');
        Route::delete('/carts/{cartId}/lines/{lineId}', [CartController::class, 'destroyLine'])->name('api.storefront.carts.lines.destroy');

        Route::middleware('throttle:checkout')->group(function (): void {
            Route::post('/checkouts', [CheckoutController::class, 'store'])->name('api.storefront.checkouts.store');
            Route::get('/checkouts/{checkoutId}', [CheckoutController::class, 'show'])->name('api.storefront.checkouts.show');
            Route::put('/checkouts/{checkoutId}/address', [CheckoutController::class, 'address'])->name('api.storefront.checkouts.address');
            Route::put('/checkouts/{checkoutId}/shipping-method', [CheckoutController::class, 'shippingMethod'])->name('api.storefront.checkouts.shipping-method');
            Route::put('/checkouts/{checkoutId}/payment-method', [CheckoutController::class, 'paymentMethod'])->name('api.storefront.checkouts.payment-method');
            Route::post('/checkouts/{checkoutId}/pay', [CheckoutController::class, 'pay'])->name('api.storefront.checkouts.pay');
            Route::post('/checkouts/{checkoutId}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('api.storefront.checkouts.apply-discount');
            Route::delete('/checkouts/{checkoutId}/discount', [CheckoutController::class, 'removeDiscount'])->name('api.storefront.checkouts.remove-discount');
        });
    });
