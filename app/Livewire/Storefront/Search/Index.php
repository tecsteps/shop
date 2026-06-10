<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Store;
use App\Services\SearchService;
use App\Services\ThemeSettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full search results page (spec 04 section 11.2): same layout as the
 * collection page with a filter sidebar, sort dropdown, product grid, and
 * pagination, driven by the FTS5 SearchService.
 */
#[Layout('layouts::storefront')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    #[Url(except: 'relevance')]
    public string $sort = 'relevance';

    #[Url(except: false)]
    public bool $inStock = false;

    #[Url(except: '')]
    public string $priceMin = '';

    #[Url(except: '')]
    public string $priceMax = '';

    /** @var list<string> */
    #[Url(except: [])]
    public array $productTypes = [];

    /** @var list<string> */
    #[Url(except: [])]
    public array $vendors = [];

    /**
     * Whether the next render should log the query (only logged on initial
     * load and when the query text changes, not for filter/page updates).
     */
    protected bool $shouldLogQuery = false;

    public function mount(): void
    {
        $this->shouldLogQuery = trim($this->query) !== '';
    }

    /**
     * Reset pagination whenever the query, a filter, or the sort changes.
     */
    public function updated(string $property): void
    {
        $watched = ['query', 'sort', 'inStock', 'priceMin', 'priceMax', 'productTypes', 'vendors'];

        if (in_array(str($property)->before('.')->value(), $watched, true)) {
            $this->resetPage();
        }

        if ($property === 'query') {
            $this->shouldLogQuery = trim($this->query) !== '';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('inStock', 'priceMin', 'priceMax', 'productTypes', 'vendors');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->inStock
            || $this->priceMin !== ''
            || $this->priceMax !== ''
            || $this->productTypes !== []
            || $this->vendors !== [];
    }

    public function render(SearchService $search, ThemeSettingsService $themeSettings): View
    {
        $perPage = (int) $themeSettings->get('products_per_page', 12);
        $products = $this->results($search, $perPage);
        $facets = trim($this->query) !== ''
            ? $search->facetValues($this->store(), $this->query)
            : ['vendors' => [], 'product_types' => []];

        return view('livewire.storefront.search.index', [
            'products' => $products,
            'availableProductTypes' => $facets['product_types'],
            'availableVendors' => $facets['vendors'],
            'hasActiveFilters' => $this->hasActiveFilters(),
        ])->title(__('Search results'));
    }

    /**
     * @return LengthAwarePaginator<int, \App\Models\Product>
     */
    protected function results(SearchService $search, int $perPage): LengthAwarePaginator
    {
        if (trim($this->query) === '') {
            return new Paginator([], 0, $perPage, 1);
        }

        $results = $search->search(
            $this->store(),
            $this->query,
            $this->filters(),
            $perPage,
            $this->sort,
            logQuery: $this->shouldLogQuery,
        );

        $this->shouldLogQuery = false;

        return $results;
    }

    /**
     * Map UI filter state to SearchService filters. Price inputs are in
     * major units (EUR) and converted to minor units.
     *
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        $filters = [];

        if ($this->vendors !== []) {
            $filters['vendors'] = $this->vendors;
        }

        if ($this->productTypes !== []) {
            $filters['product_types'] = $this->productTypes;
        }

        if ($this->inStock) {
            $filters['in_stock'] = true;
        }

        if ($this->priceMin !== '' && is_numeric($this->priceMin)) {
            $filters['price_min'] = (int) round((float) $this->priceMin * 100);
        }

        if ($this->priceMax !== '' && is_numeric($this->priceMax)) {
            $filters['price_max'] = (int) round((float) $this->priceMax * 100);
        }

        return $filters;
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
