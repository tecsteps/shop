<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\QuickAddsProducts;
use App\Models\Product;
use Illuminate\View\View;
use Livewire\Attributes\Lazy;

#[Lazy]
class FeaturedProducts extends StorefrontComponent
{
    use QuickAddsProducts;

    public int $limit = 8;

    public function placeholder(): View
    {
        return view('storefront.components.product-grid-skeleton', ['count' => $this->limit]);
    }

    public function render(): View
    {
        $products = Product::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->with(['variants.inventoryItem', 'media'])
            ->latest('published_at')
            ->limit($this->limit)
            ->get();

        return view('storefront.components.featured-products', compact('products'));
    }
}
