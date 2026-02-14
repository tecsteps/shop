<?php

use App\Http\Controllers\Api\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\Admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\Api\Admin\MeController as AdminMeController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Storefront\AnalyticsEventController;
use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\OrderController;
use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')
    ->middleware('store.resolve')
    ->name('api.storefront.')
    ->group(function (): void {
        Route::get('/health', fn (): array => ['ok' => true])
            ->middleware('throttle:api.storefront')
            ->name('health');

        Route::prefix('carts')
            ->middleware('throttle:api.storefront')
            ->name('carts.')
            ->group(function (): void {
                Route::post('/', [CartController::class, 'store'])->name('store');
                Route::get('/{cart}', [CartController::class, 'show'])->name('show');
                Route::post('/{cart}/lines', [CartController::class, 'addLine'])->name('lines.store');
                Route::put('/{cart}/lines/{line}', [CartController::class, 'updateLine'])->name('lines.update');
                Route::delete('/{cart}/lines/{line}', [CartController::class, 'removeLine'])->name('lines.destroy');
            });

        Route::prefix('checkouts')
            ->middleware('throttle:checkout')
            ->name('checkouts.')
            ->group(function (): void {
                Route::post('/', [CheckoutController::class, 'store'])->name('store');
                Route::get('/{checkout}', [CheckoutController::class, 'show'])->name('show');
                Route::put('/{checkout}/address', [CheckoutController::class, 'updateAddress'])->name('address');
                Route::put('/{checkout}/shipping-method', [CheckoutController::class, 'selectShippingMethod'])->name('shipping-method');
                Route::put('/{checkout}/payment-method', [CheckoutController::class, 'selectPaymentMethod'])->name('payment-method');
                Route::post('/{checkout}/apply-discount', [CheckoutController::class, 'applyDiscount'])->name('apply-discount');
                Route::delete('/{checkout}/discount', [CheckoutController::class, 'removeDiscount'])->name('remove-discount');
                Route::post('/{checkout}/pay', [CheckoutController::class, 'pay'])->name('pay');
            });

        Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])
            ->middleware('throttle:api.storefront')
            ->name('orders.show');

        Route::prefix('search')
            ->middleware('throttle:search')
            ->name('search.')
            ->group(function (): void {
                Route::get('/', [SearchController::class, 'search'])->name('index');
                Route::get('/suggest', [SearchController::class, 'suggest'])->name('suggest');
            });

        Route::prefix('analytics')
            ->middleware('throttle:analytics')
            ->name('analytics.')
            ->group(function (): void {
                Route::post('/events', [AnalyticsEventController::class, 'store'])->name('events.store');
            });
    });

Route::prefix('admin/v1')
    ->middleware(['auth:sanctum', 'store.resolve', 'throttle:api.admin'])
    ->name('api.admin.')
    ->group(function (): void {
        Route::get('/me', [AdminMeController::class, 'show'])->name('me');

        Route::prefix('stores/{store}')
            ->group(function (): void {
                Route::apiResource('products', AdminProductController::class)
                    ->names([
                        'index' => 'products.index',
                        'store' => 'products.store',
                        'show' => 'products.show',
                        'update' => 'products.update',
                        'destroy' => 'products.destroy',
                    ]);

                Route::apiResource('collections', AdminCollectionController::class)
                    ->names([
                        'index' => 'collections.index',
                        'store' => 'collections.store',
                        'show' => 'collections.show',
                        'update' => 'collections.update',
                        'destroy' => 'collections.destroy',
                    ]);

                Route::apiResource('discounts', AdminDiscountController::class)
                    ->names([
                        'index' => 'discounts.index',
                        'store' => 'discounts.store',
                        'show' => 'discounts.show',
                        'update' => 'discounts.update',
                        'destroy' => 'discounts.destroy',
                    ]);
            });
    });
