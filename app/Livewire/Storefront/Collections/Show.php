<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\ProductVariant;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    /**
     * Products per page on the collection grid.
     */
    public const PER_PAGE = 12;

    /**
     * Available sort options keyed by query value.
     *
     * @var array<string, string>
     */
    public const SORT_OPTIONS = [
        'featured' => 'Featured',
        'price-asc' => 'Price: Low to High',
        'price-desc' => 'Price: High to Low',
        'newest' => 'Newest',
        'best-selling' => 'Best Selling',
    ];

    public Collection $collection;

    public string $sort = 'featured';

    public bool $inStock = false;

    public ?string $minPrice = null;

    public ?string $maxPrice = null;

    /** @var list<string> */
    public array $types = [];

    /** @var list<string> */
    public array $vendors = [];

    /**
     * Resolve the collection by handle; only active collections are visible.
     */
    public function mount(string $handle): void
    {
        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->firstOrFail();
    }

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
        $this->reset(['inStock', 'minPrice', 'maxPrice', 'types', 'vendors']);
        $this->resetPage();
    }

    /**
     * Render the collection page.
     */
    public function render(): View
    {
        $products = $this->collection->products()
            ->visible()
            ->with(['variants.inventoryItem', 'media']);

        $this->applyFilters($products);
        $this->applySort($products);

        $paginator = $products->paginate(self::PER_PAGE);

        $storeName = app('current_store')->name;

        return view('livewire.storefront.collections.show', [
            'products' => $paginator,
            'sortOptions' => self::SORT_OPTIONS,
            'availableTypes' => $this->availableFilterValues('product_type'),
            'availableVendors' => $this->availableFilterValues('vendor'),
            'activeFilters' => $this->activeFilters(),
        ])
            ->layout('storefront.layouts.app', [
                'metaDescription' => str()->limit(trim(strip_tags($this->collection->description_html ?? '')), 160, ''),
            ])
            ->title("{$this->collection->title} - {$storeName}");
    }

    /**
     * Apply the active filters to the product query.
     *
     * @param  BelongsToMany<\App\Models\Product>  $query
     */
    private function applyFilters(BelongsToMany $query): void
    {
        if ($this->inStock) {
            $query->whereHas('variants.inventoryItem', function ($q): void {
                $q->whereRaw('(quantity_on_hand - quantity_reserved) > 0');
            });
        }

        $minCents = $this->priceToCents($this->minPrice);
        if ($minCents !== null) {
            $query->whereHas('variants', fn ($q) => $q->where('price_amount', '>=', $minCents));
        }

        $maxCents = $this->priceToCents($this->maxPrice);
        if ($maxCents !== null) {
            $query->whereHas('variants', fn ($q) => $q->where('price_amount', '<=', $maxCents));
        }

        if ($this->types !== []) {
            $query->whereIn('products.product_type', $this->types);
        }

        if ($this->vendors !== []) {
            $query->whereIn('products.vendor', $this->vendors);
        }
    }

    /**
     * Apply the selected sort order to the product query.
     *
     * @param  BelongsToMany<\App\Models\Product>  $query
     */
    private function applySort(BelongsToMany $query): void
    {
        if ($this->sort !== 'featured') {
            // The products() relation has a default orderBy on the pivot
            // position; a later orderBy would only act as a tie-breaker, so
            // reset the ordering before applying the requested sort.
            $query->reorder();
        }

        match ($this->sort) {
            'price-asc' => $query->orderBy($this->minimumPriceSubquery()),
            'price-desc' => $query->orderByDesc($this->minimumPriceSubquery()),
            'newest' => $query->orderByDesc('products.created_at'),
            'best-selling' => $query->orderByDesc($this->salesCountSubquery()),
            default => $query->orderBy('collection_products.position'),
        };
    }

    /**
     * Subquery selecting the minimum variant price of a product.
     */
    private function minimumPriceSubquery(): BuilderContract
    {
        return ProductVariant::query()
            ->selectRaw('MIN(price_amount)')
            ->whereColumn('product_variants.product_id', 'products.id');
    }

    /**
     * Subquery selecting the total sold quantity of a product.
     */
    private function salesCountSubquery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_lines')
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('order_lines.product_id', 'products.id');
    }

    /**
     * Distinct non-null values of a product attribute within this collection.
     *
     * @return list<string>
     */
    private function availableFilterValues(string $column): array
    {
        return $this->collection->products()
            ->visible()
            ->whereNotNull("products.{$column}")
            ->distinct()
            ->reorder("products.{$column}")
            ->pluck("products.{$column}")
            ->all();
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
        foreach ($this->types as $type) {
            $active[] = "Type: {$type}";
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
