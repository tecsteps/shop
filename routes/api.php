<?php

use App\Http\Controllers\Api\Storefront\V1\CartController;
use App\Http\Controllers\Api\Storefront\V1\CheckoutController;
use App\Http\Controllers\Api\Storefront\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')
    ->middleware(['store.resolve:storefront', 'throttle:api.storefront'])
    ->group(function () {
        // Cart
        Route::post('/carts', [CartController::class, 'store']);
        Route::get('/carts/{cart}', [CartController::class, 'show']);
        Route::post('/carts/{cart}/lines', [CartController::class, 'addLine']);
        Route::put('/carts/{cart}/lines/{line}', [CartController::class, 'updateLine']);
        Route::delete('/carts/{cart}/lines/{line}', [CartController::class, 'removeLine']);

        // Checkout
        Route::post('/checkouts', [CheckoutController::class, 'store']);
        Route::put('/checkouts/{checkout}/address', [CheckoutController::class, 'setAddress']);
        Route::put('/checkouts/{checkout}/shipping-method', [CheckoutController::class, 'setShippingMethod']);
        Route::post('/checkouts/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount']);
        Route::post('/checkouts/{checkout}/pay', [CheckoutController::class, 'pay']);

        // Search
        Route::get('/search/suggest', [SearchController::class, 'suggest']);
    });
