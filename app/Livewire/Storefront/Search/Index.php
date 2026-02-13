<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $query = '';

    public string $sort = 'relevance';

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    /**
     * @var array<int, string>
     */
    public array $selectedVendors = [];

    /**
     * @var array<int, string>
     */
    public array $selectedTypes = [];

    public function mount(): void
    {
        $this->query = request()->get('q', '');
    }

    public function updatedSort(): void
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

    public function updatedSelectedVendors(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedTypes(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->selectedVendors = [];
        $this->selectedTypes = [];
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->minPrice !== null
            || $this->maxPrice !== null
            || count($this->selectedVendors) > 0
            || count($this->selectedTypes) > 0;
    }

    public function render(): View
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        $products = null;
        $vendors = [];
        $productTypes = [];

        if ($store && $this->query !== '') {
            $filters = [
                'sort' => $this->sort,
            ];

            if (count($this->selectedVendors) > 0) {
                $filters['vendors'] = $this->selectedVendors;
            }

            if (count($this->selectedTypes) > 0) {
                $filters['product_types'] = $this->selectedTypes;
            }

            if ($this->minPrice !== null) {
                $filters['min_price'] = $this->minPrice * 100;
            }

            if ($this->maxPrice !== null) {
                $filters['max_price'] = $this->maxPrice * 100;
            }

            $searchService = app(SearchService::class);
            $products = $searchService->search($store, $this->query, $filters, 12);

            // Get available filter values from active products in this store
            $allProducts = Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', ProductStatus::Active)
                ->whereNotNull('published_at');

            $vendors = (clone $allProducts)
                ->whereNotNull('vendor')
                ->distinct()
                ->pluck('vendor')
                ->toArray();

            $productTypes = (clone $allProducts)
                ->whereNotNull('product_type')
                ->distinct()
                ->pluck('product_type')
                ->toArray();
        }

        $title = $this->query
            ? 'Search results for "'.$this->query.'"'
            : 'Search';

        return view('livewire.storefront.search.index', [
            'products' => $products,
            'vendors' => $vendors,
            'productTypes' => $productTypes,
        ])->layout('storefront.layouts.app', [
            'title' => $title,
        ]);
    }
}
