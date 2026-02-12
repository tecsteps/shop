<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\ProductStatus;
use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class Show extends Component
{
    use WithPagination;

    public string $handle = '';

    #[Url]
    public string $sort = 'featured';

    public function render()
    {
        $store = app('current_store');

        $collection = Collection::where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->active()
            ->firstOrFail();

        $query = $collection->products()
            ->where('status', ProductStatus::Active)
            ->with(['variants.inventoryItem', 'media']);

        $query = match ($this->sort) {
            'price-asc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC'),
            'price-desc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC'),
            'title-asc' => $query->orderBy('products.title', 'asc'),
            'newest' => $query->orderBy('products.created_at', 'desc'),
            default => $query->orderBy('collection_products.position', 'asc'),
        };

        $products = $query->paginate(12);

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ])->title($collection->title);
    }
}
