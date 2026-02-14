<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends StorefrontController
{
    public function show(Request $request, string $handle): View
    {
        $product = Product::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active->value)
            ->whereNotNull('published_at')
            ->with([
                'variants' => fn ($query) => $query
                    ->where('status', ProductVariantStatus::Active->value)
                    ->orderByDesc('is_default')
                    ->orderBy('position')
                    ->orderBy('price_amount'),
                'collections' => fn ($query) => $query
                    ->where('collections.status', CollectionStatus::Active->value)
                    ->orderBy('collections.title'),
            ])
            ->firstOrFail();

        return view('storefront.products.show', [
            'product' => $product,
        ]);
    }
}
