<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection as ProductCollection;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public ProductCollection $collection;

    public string $sort = 'featured';

    public bool $inStock = false;

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedInStock(): void
    {
        $this->resetPage();
    }

    public function mount(string $handle): void
    {
        $this->collection = ProductCollection::query()->where('handle', $handle)->with(['products.variants.inventory', 'products.media'])->firstOrFail();
    }

    public function render(): mixed
    {
        $products = $this->collection->products()
            ->where('products.status', 'active')
            ->with(['variants.inventory', 'media'])
            ->when($this->inStock, fn ($query) => $query->whereHas('variants.inventory', fn ($inventory) => $inventory->whereColumn('quantity_on_hand', '>', 'quantity_reserved')->orWhere('policy', 'continue')))
            ->when($this->sort === 'price_asc', fn ($query) => $query->withMin('variants', 'price_amount')->orderBy('variants_min_price_amount'))
            ->when($this->sort === 'price_desc', fn ($query) => $query->withMin('variants', 'price_amount')->orderByDesc('variants_min_price_amount'))
            ->when($this->sort === 'newest', fn ($query) => $query->latest('products.created_at'))
            ->paginate(12);

        return view('livewire.storefront.collections.show', ['products' => $products])->layout('layouts.storefront');
    }

    public function clearFilters(): void
    {
        $this->inStock = false;
        $this->sort = 'featured';
    }
}
