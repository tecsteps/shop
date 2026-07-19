<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full search results page with filters, sort and pagination (spec 04 §11.2).
 */
class Index extends Component
{
    use WithPagination;

    /**
     * Products per page on the results grid (same as the collection page).
     */
    public const PER_PAGE = 12;

    /**
     * Available sort options keyed by query value (spec 02 §2.5).
     *
     * @var array<string, string>
     */
    public const SORT_OPTIONS = [
        'relevance' => 'Relevance',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'newest' => 'Newest',
        'best_selling' => 'Best Selling',
    ];

    #[Url(as: 'q')]
    public string $query = '';

    public string $sort = 'relevance';

    public bool $inStock = false;

    public ?string $minPrice = null;

    public ?string $maxPrice = null;

    public ?int $collectionId = null;

    /** @var list<string> */
    public array $vendors = [];

    /**
     * Any filter or sort change returns to the first page.
     */
    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * Reset every filter to its default.
     */
    public function clearFilters(): void
    {
        $this->reset(['inStock', 'minPrice', 'maxPrice', 'collectionId', 'vendors']);
        $this->resetPage();
    }

    /**
     * Render the results page.
     */
    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $products = null;
        $facets = null;

        if (trim($this->query) !== '') {
            $service = app(SearchService::class);

            $products = $service->search($store, $this->query, $this->filters(), self::PER_PAGE, $this->sort);
            $facets = $service->facets($store, $this->query);
        }

        return view('livewire.storefront.search.index', [
            'products' => $products,
            'facets' => $facets,
            'sortOptions' => self::SORT_OPTIONS,
            'availableCollections' => Collection::query()
                ->where('status', CollectionStatus::Active)
                ->orderBy('title')
                ->get(['id', 'title']),
            'activeFilters' => $this->activeFilters(),
        ])
            ->layout('storefront.layouts.app')
            ->title("Search - {$store->name}");
    }

    /**
     * Active filters in the service's filter schema (spec 02 §2.5).
     *
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return array_filter([
            'collection_id' => $this->collectionId,
            'price_min' => $this->priceToCents($this->minPrice),
            'price_max' => $this->priceToCents($this->maxPrice),
            'in_stock' => $this->inStock ?: null,
            'vendor' => $this->vendors !== [] ? $this->vendors : null,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * Human-readable list of the currently active filters.
     *
     * @return list<string>
     */
    private function activeFilters(): array
    {
        $active = [];

        if ($this->inStock) {
            $active[] = 'In stock';
        }
        if ($this->priceToCents($this->minPrice) !== null) {
            $active[] = "Min {$this->minPrice}";
        }
        if ($this->priceToCents($this->maxPrice) !== null) {
            $active[] = "Max {$this->maxPrice}";
        }
        if ($this->collectionId !== null) {
            $active[] = 'Collection filter';
        }
        foreach ($this->vendors as $vendor) {
            $active[] = "Vendor: {$vendor}";
        }

        return $active;
    }

    /**
     * Convert a user-entered major-unit price into cents.
     */
    private function priceToCents(?string $price): ?int
    {
        if ($price === null || trim($price) === '' || ! is_numeric($price)) {
            return null;
        }

        return max(0, (int) round(((float) $price) * 100));
    }
}
