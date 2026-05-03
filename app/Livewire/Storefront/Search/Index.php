<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $q = '';

    public string $vendor = '';

    public string $productType = '';

    public string $minPrice = '';

    public string $maxPrice = '';

    public bool $inStock = false;

    public string $sort = 'relevance';

    protected $queryString = [
        'q' => ['except' => ''],
        'vendor' => ['except' => ''],
        'productType' => ['except' => ''],
        'minPrice' => ['except' => ''],
        'maxPrice' => ['except' => ''],
        'inStock' => ['except' => false],
        'sort' => ['except' => 'relevance'],
    ];

    public function updated(string $property, mixed $value): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('vendor', 'productType', 'minPrice', 'maxPrice', 'inStock', 'sort');
        $this->resetPage();
    }

    public function render(): View
    {
        $store = app('current_store');
        $search = app(SearchService::class);

        return view('livewire.storefront.search.index', [
            'products' => $search->search($store, $this->q, $this->filters(), 24, $this->sort),
            'facets' => $search->facets($store, $this->q),
            'store' => $store,
        ])->layout('storefront.layouts.app', [
            'title' => 'Search',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return collect([
            'vendor' => $this->vendor,
            'product_type' => $this->productType,
            'price_min' => $this->priceToCents($this->minPrice),
            'price_max' => $this->priceToCents($this->maxPrice),
            'in_stock' => $this->inStock,
        ])
            ->reject(fn (mixed $value): bool => $value === '' || $value === null || $value === false)
            ->all();
    }

    private function priceToCents(string $value): ?int
    {
        $value = trim(str_replace(',', '.', $value));

        if ($value === '') {
            return null;
        }

        return max(0, (int) round(((float) $value) * 100));
    }
}
