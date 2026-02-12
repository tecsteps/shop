<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductController extends StorefrontController
{
    public function show(Request $request, string $handle)
    {
        $product = Product::query()
            ->where('status', ProductStatus::Active)
            ->where('handle', $handle)
            ->with([
                'media' => fn ($query) => $query->orderBy('position'),
                'collections' => fn ($query) => $query->where('status', CollectionStatus::Active),
                'options' => fn ($query) => $query->orderBy('position')->with([
                    'values' => fn ($values) => $values->orderBy('position'),
                ]),
                'variants' => fn ($query) => $query
                    ->where('status', VariantStatus::Active)
                    ->orderBy('position')
                    ->with(['inventoryItem', 'optionValues.option']),
            ])
            ->firstOrFail();

        $variantId = (int) $request->query('variant', 0);

        $selectedVariant = $product->variants->firstWhere('id', $variantId)
            ?: $product->variants->firstWhere('is_default', true)
            ?: $product->variants->first();

        abort_if(! $selectedVariant instanceof ProductVariant, 404);

        $relatedProducts = Product::query()
            ->where('status', ProductStatus::Active)
            ->whereKeyNot($product->id)
            ->whereHas('collections', function ($query) use ($product): void {
                $query->whereIn('collections.id', $product->collections->pluck('id'));
            })
            ->with([
                'media' => fn ($query) => $query->orderBy('position'),
                'defaultVariant.inventoryItem',
            ])
            ->limit(4)
            ->get();

        return view('storefront.products.show', $this->storefrontViewData($request, [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
            'relatedProducts' => $relatedProducts,
            'title' => $product->title,
            'metaDescription' => strip_tags((string) $product->description_html),
        ]));
    }
}
