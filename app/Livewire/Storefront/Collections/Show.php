<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    public string $sort = 'newest';

    public string $search = '';

    public function mount(string $handle): void
    {
        $this->collection = Collection::query()->where('handle', $handle)->where('status', 'active')->firstOrFail();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $products = Product::published()
            ->whereHas('collections', fn ($query) => $query->whereKey($this->collection))
            ->with(['variants.inventoryItem', 'media'])
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'));

        match ($this->sort) {
            'price-low' => $products->withMin('variants', 'price_amount')->orderBy('variants_min_price_amount'),
            'price-high' => $products->withMax('variants', 'price_amount')->orderByDesc('variants_max_price_amount'),
            default => $products->latest('published_at'),
        };

        return view('livewire.storefront.collections.show', ['products' => $products->paginate(12)])
            ->layout('layouts.storefront', ['title' => $this->collection->title.' - '.app('current_store')->name]);
    }
}
