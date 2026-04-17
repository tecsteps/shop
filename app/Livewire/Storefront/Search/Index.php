<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url]
    public string $sort = 'relevance';

    #[Url]
    public ?string $vendor = null;

    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedVendor(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->vendor = null;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        $products = null;
        $totalResults = 0;

        if ($store && $this->query !== '') {
            $searchService = app(SearchService::class);

            $filters = array_filter([
                'sort' => $this->sort,
                'vendor' => $this->vendor,
                'min_price' => $this->minPrice,
                'max_price' => $this->maxPrice,
            ]);

            $products = $searchService->search($store, $this->query, $filters, 12);
            $totalResults = $products->total();
        }

        return view('livewire.storefront.search.index', [
            'products' => $products,
            'totalResults' => $totalResults,
        ]);
    }
}
