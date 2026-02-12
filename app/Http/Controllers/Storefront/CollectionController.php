<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends StorefrontController
{
    public function index(Request $request)
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->withCount('products')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.collections.index', $this->storefrontViewData($request, [
            'collections' => $collections,
            'title' => 'Collections',
            'metaDescription' => 'Browse all collections.',
        ]));
    }

    public function show(Request $request, string $handle)
    {
        $collection = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->where('handle', $handle)
            ->firstOrFail();

        $sort = (string) $request->query('sort', 'featured');

        $productsQuery = $collection->products()
            ->where('products.status', ProductStatus::Active)
            ->whereHas('variants', fn ($query) => $query->where('status', VariantStatus::Active))
            ->withMin('variants as min_price_amount', 'price_amount')
            ->with([
                'media' => fn ($query) => $query->orderBy('position'),
                'defaultVariant.inventoryItem',
            ]);

        match ($sort) {
            'price_low' => $productsQuery->orderBy('min_price_amount')->orderBy('products.title'),
            'price_high' => $productsQuery->orderByDesc('min_price_amount')->orderBy('products.title'),
            'newest' => $productsQuery->orderByDesc('products.published_at')->orderByDesc('products.id'),
            'best_selling' => $productsQuery->orderByDesc('products.id'),
            default => $productsQuery->orderBy('collection_products.position')->orderBy('products.title'),
        };

        $productCount = (clone $productsQuery)->count();

        $products = $productsQuery
            ->paginate(12)
            ->withQueryString();

        return view('storefront.collections.show', $this->storefrontViewData($request, [
            'collection' => $collection,
            'products' => $products,
            'productCount' => $productCount,
            'sort' => $sort,
            'title' => $collection->title,
            'metaDescription' => strip_tags((string) $collection->description_html),
        ]));
    }
}
