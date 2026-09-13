<?php

use App\Http\Controllers\Api\Admin\V1\MeController;
use App\Http\Controllers\Api\Admin\V1\Platform\OrganizationController;
use App\Http\Controllers\Api\Admin\V1\Platform\StoreController as PlatformStoreController;
use App\Http\Controllers\Api\Admin\V1\StoreInviteController;
use App\Http\Controllers\Api\Admin\V1\TokenController;
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
    });

    Route::middleware(['web', 'auth', 'throttle:api.admin'])->group(function (): void {
        Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
        Route::delete('tokens/{tokenId}', [TokenController::class, 'destroy'])->name('tokens.destroy');
    });
});
