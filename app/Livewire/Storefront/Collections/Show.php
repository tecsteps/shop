<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public string $sortBy = 'featured';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $collection = Collection::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->where('status', CollectionStatus::Active)
            ->first();

        if (! $collection) {
            abort(404);
        }

        $productsQuery = $collection->products()
            ->where('products.status', ProductStatus::Active)
            ->with(['variants' => fn ($q) => $q->where('is_default', true)]);

        $products = match ($this->sortBy) {
            'price_asc' => $productsQuery->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND is_default = 1 LIMIT 1) ASC')->get(),
            'price_desc' => $productsQuery->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND is_default = 1 LIMIT 1) DESC')->get(),
            'newest' => $productsQuery->orderByDesc('products.created_at')->get(),
            default => $productsQuery->orderBy('collection_products.position')->get(),
        };

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ]);
    }
}
