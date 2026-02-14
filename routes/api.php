<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront/v1')
    ->middleware('store.resolve')
    ->name('api.storefront.')
    ->group(function (): void {
        Route::get('/health', fn (): array => ['ok' => true])
            ->middleware('throttle:api.storefront')
            ->name('health');

        Route::prefix('checkouts')
            ->middleware('throttle:checkout')
            ->group(function (): void {
                Route::post('/', fn (): array => ['message' => 'Checkout create placeholder'])->name('checkouts.store');
                Route::get('/{checkoutId}', fn (string $checkoutId): array => ['checkout_id' => $checkoutId, 'message' => 'Checkout show placeholder'])->name('checkouts.show');
                Route::put('/{checkoutId}/address', fn (string $checkoutId): array => ['checkout_id' => $checkoutId, 'message' => 'Checkout address placeholder'])->name('checkouts.address');
                Route::post('/{checkoutId}/pay', fn (string $checkoutId): array => ['checkout_id' => $checkoutId, 'message' => 'Checkout payment placeholder'])->name('checkouts.pay');
            });

        Route::get('/search', fn (): array => ['message' => 'Storefront search placeholder'])
            ->middleware('throttle:search')
            ->name('search');

        Route::post('/analytics/events', fn (): array => ['message' => 'Analytics ingestion placeholder'])
            ->middleware('throttle:analytics')
            ->name('analytics.events');
    });

Route::prefix('admin/v1')
    ->middleware(['auth:sanctum', 'store.resolve', 'throttle:api.admin'])
    ->name('api.admin.')
    ->group(function (): void {
        Route::get('/me', function (Request $request): array {
            return [
                'message' => 'Admin API placeholder',
                'user_id' => $request->user()?->getAuthIdentifier(),
            ];
        })->name('me');
    });
