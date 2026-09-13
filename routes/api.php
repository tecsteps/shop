<?php

use App\Http\Controllers\Api\Admin\V1\CollectionController;
use App\Http\Controllers\Api\Admin\V1\MeController;
use App\Http\Controllers\Api\Admin\V1\Platform\OrganizationController;
use App\Http\Controllers\Api\Admin\V1\Platform\StoreController as PlatformStoreController;
use App\Http\Controllers\Api\Admin\V1\ProductController;
use App\Http\Controllers\Api\Admin\V1\ProductMediaController;
use App\Http\Controllers\Api\Admin\V1\SearchAdminController;
use App\Http\Controllers\Api\Admin\V1\StoreInviteController;
use App\Http\Controllers\Api\Admin\V1\TokenController;
use App\Http\Controllers\Api\Admin\V1\VariantInventoryController;
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

        Route::post('stores/{storeId}/search/reindex', [SearchAdminController::class, 'reindex'])
            ->middleware('abilities:write-settings')
            ->name('stores.search.reindex');

        Route::get('stores/{storeId}/search/status', [SearchAdminController::class, 'status'])
            ->middleware('abilities:read-settings')
            ->name('stores.search.status');
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
