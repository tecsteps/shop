<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends StorefrontController
{
    public function index(Request $request): View
    {
        $storeId = $this->currentStoreId($request);

        $collections = Collection::query()
            ->where('store_id', $storeId)
            ->where('status', CollectionStatus::Active->value)
            ->withCount('products')
            ->orderByDesc('updated_at')
            ->orderBy('title')
            ->limit(6)
            ->get();

        $products = Product::query()
            ->where('store_id', $storeId)
            ->where('status', ProductStatus::Active->value)
            ->whereNotNull('published_at')
            ->with([
                'variants' => fn ($query) => $query
                    ->where('status', ProductVariantStatus::Active->value)
                    ->orderByDesc('is_default')
                    ->orderBy('position')
                    ->orderBy('price_amount'),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('storefront.home', [
            'collections' => $collections,
            'products' => $products,
        ]);
    }
}
