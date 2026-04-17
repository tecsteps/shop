<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CollectionController extends StorefrontController
{
    public function index(Request $request): View
    {
        $collections = Collection::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('status', CollectionStatus::Active->value)
            ->withCount([
                'products as active_products_count' => fn ($query) => $query
                    ->where('products.status', ProductStatus::Active->value)
                    ->whereNotNull('products.published_at'),
            ])
            ->orderBy('title')
            ->paginate(18)
            ->withQueryString();

        return view('storefront.collections.index', [
            'collections' => $collections,
        ]);
    }

    public function show(Request $request, string $handle): View
    {
        $collection = Collection::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active->value)
            ->firstOrFail();

        $collection->load([
            'products' => fn ($query) => $query
                ->where('products.status', ProductStatus::Active->value)
                ->whereNotNull('products.published_at')
                ->with([
                    'variants' => fn ($variantQuery) => $variantQuery
                        ->where('status', ProductVariantStatus::Active->value)
                        ->orderByDesc('is_default')
                        ->orderBy('position')
                        ->orderBy('price_amount'),
                ])
                ->orderBy('collection_products.position')
                ->orderBy('products.title'),
        ]);

        return view('storefront.collections.show', [
            'collection' => $collection,
        ]);
    }
}
