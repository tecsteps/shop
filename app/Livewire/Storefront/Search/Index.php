<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = collect();
        $collections = collect();

        if (trim($this->query) !== '') {
            $products = Product::query()
                ->where('status', ProductStatus::Active)
                ->where('title', 'like', '%'.$this->query.'%')
                ->with(['variants', 'media'])
                ->paginate(12)
                ->withQueryString();

            $collections = Collection::query()
                ->where('title', 'like', '%'.$this->query.'%')
                ->limit(6)
                ->get();
        }

        return view('livewire.storefront.search.index', [
            'products' => $products,
            'collections' => $collections,
        ])
            ->layout('layouts.storefront')
            ->title('Search - '.app('current_store')->name);
    }
}
