<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $q = '';

    public function mount(): void
    {
        $this->q = (string) request('q', '');
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['variants.inventoryItem'])
            ->withCount('variants')
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->when(trim($this->q) !== '', function (Builder $query): void {
                $search = '%'.trim($this->q).'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', $search)
                        ->orWhere('vendor', 'like', $search)
                        ->orWhere('product_type', 'like', $search)
                        ->orWhere('tags', 'like', $search);
                });
            })
            ->latest('published_at')
            ->paginate(12);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.search.index', [
            'products' => $this->products(),
        ])->layout('layouts.storefront', [
            'title' => __('Search results'),
        ]);
    }
}
