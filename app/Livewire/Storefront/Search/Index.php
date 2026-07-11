<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $query = '';

    public function mount(): void
    {
        $this->query = (string) request()->query('q', '');
    }

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $products = Product::published()->with(['variants.inventoryItem', 'media'])
            ->when($this->query !== '', fn ($builder) => $builder->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->query.'%')->orWhere('vendor', 'like', '%'.$this->query.'%');
            }))
            ->latest('published_at')
            ->paginate(12);

        return view('livewire.storefront.search.index', ['products' => $products])
            ->layout('layouts.storefront', ['title' => 'Search - '.app('current_store')->name]);
    }
}
