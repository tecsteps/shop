<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Search')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $sort = 'relevance';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $store = app('current_store');
        $products = null;

        if (strlen($this->q) >= 2) {
            $query = Product::where('store_id', $store->id)
                ->active()
                ->where('title', 'LIKE', '%'.$this->q.'%')
                ->with(['variants.inventoryItem', 'media']);

            $query = match ($this->sort) {
                'price-asc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC'),
                'price-desc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC'),
                'title-asc' => $query->orderBy('title', 'asc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                default => $query->orderBy('title'),
            };

            $products = $query->paginate(12);
        }

        return view('livewire.storefront.search.index', [
            'products' => $products,
        ]);
    }
}
