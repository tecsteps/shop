<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $q = '';

    public string $sort = 'relevance';

    public bool $inStock = false;

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    /**
     * @var array<int, string>
     */
    public array $types = [];

    /**
     * @var array<int, string>
     */
    public array $vendors = [];

    public function mount(): void
    {
        $this->q = (string) request('q', '');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->sort = 'relevance';
        $this->inStock = false;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->types = [];
        $this->vendors = [];
        $this->resetPage();
    }

    public function products(): LengthAwarePaginator
    {
        return app(SearchService::class)->search(
            $this->store(),
            $this->q,
            $this->filters(),
            12,
            $this->sort,
        );
    }

    public function productTypes(): Collection
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $this->store()->getKey())
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->whereNotNull('product_type')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type');
    }

    public function productVendors(): Collection
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $this->store()->getKey())
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->whereNotNull('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.search.index', [
            'products' => $this->products(),
            'productTypes' => $this->productTypes(),
            'productVendors' => $this->productVendors(),
        ])->layout('layouts.storefront', [
            'title' => __('Search results'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'in_stock' => $this->inStock,
            'price_min' => $this->minPrice === null ? null : $this->minPrice * 100,
            'price_max' => $this->maxPrice === null ? null : $this->maxPrice * 100,
            'product_type' => $this->types,
            'vendor' => $this->vendors,
        ];
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
