<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends StorefrontController
{
    public function __invoke(Request $request)
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->withCount('products')
            ->orderBy('title')
            ->limit(4)
            ->get();

        $products = Product::query()
            ->where('status', ProductStatus::Active)
            ->whereHas('variants', fn ($query) => $query->where('status', VariantStatus::Active))
            ->with([
                'media' => fn ($query) => $query->orderBy('position'),
                'defaultVariant.inventoryItem',
            ])
            ->latest('published_at')
            ->limit(8)
            ->get();

        return view('storefront.home', $this->storefrontViewData($request, [
            'collections' => $collections,
            'products' => $products,
            'title' => $this->currentStore()->name,
            'metaDescription' => 'Discover our newest collections and featured products.',
        ]));
    }
}
