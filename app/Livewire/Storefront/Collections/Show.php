<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Storefront\ProductCardPresenter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A single collection page: product grid with availability/price/type/vendor
 * filters, sorting, and pagination.
 *
 * Filter and sort state is mirrored in the query string so collection views are
 * shareable and back-button friendly. The product query is constrained to the
 * collection's active products; results are normalized through
 * {@see ProductCardPresenter} for the card component.
 */
#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use WithPagination;

    public ProductCollection $collection;

    #[Url(as: 'sort')]
    public string $sort = 'featured';

    #[Url(as: 'in_stock')]
    public bool $inStockOnly = false;

    #[Url(as: 'min')]
    public ?string $minPrice = null;

    #[Url(as: 'max')]
    public ?string $maxPrice = null;

    /** @var list<string> */
    #[Url(as: 'type')]
    public array $types = [];

    /** @var list<string> */
    #[Url(as: 'vendor')]
    public array $vendors = [];

    /**
     * Resolve the collection by its handle within the current store, 404 when
     * missing or not active.
     */
    public function mount(string $handle): void
    {
        $this->collection = ProductCollection::query()
            ->published()
            ->where('handle', $handle)
            ->firstOrFail();
    }

    /**
     * Reset pagination whenever a filter or sort changes.
     */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    /**
     * Clear all active filters.
     */
    public function clearFilters(): void
    {
        $this->reset(['inStockOnly', 'minPrice', 'maxPrice', 'types', 'vendors']);
        $this->resetPage();
    }

    public function render()
    {
        $products = $this->buildQuery()->paginate(12);

        $cards = $products->getCollection()
            ->map(fn (Product $product): array => ProductCardPresenter::fromProduct($product));

        return view('livewire.storefront.collections.show', [
            'products' => $products,
            'cards' => $cards,
            'availableTypes' => $this->availableValues('product_type'),
            'availableVendors' => $this->availableValues('vendor'),
            'hasActiveFilters' => $this->hasActiveFilters(),
        ]);
    }

    /**
     * Build the filtered, sorted product query for this collection.
     *
     * @return Builder<Product>
     */
    private function buildQuery(): Builder
    {
        $query = $this->collection->products()
            ->getQuery()
            ->published()
            ->with(['variants.inventoryItem', 'media']);

        if ($this->types !== []) {
            $query->whereIn('products.product_type', $this->types);
        }

        if ($this->vendors !== []) {
            $query->whereIn('products.vendor', $this->vendors);
        }

        if ($this->minPrice !== null && $this->minPrice !== '') {
            $query->whereHas('variants', fn (Builder $q) => $q->where('price_amount', '>=', (int) round((float) $this->minPrice * 100)));
        }

        if ($this->maxPrice !== null && $this->maxPrice !== '') {
            $query->whereHas('variants', fn (Builder $q) => $q->where('price_amount', '<=', (int) round((float) $this->maxPrice * 100)));
        }

        if ($this->inStockOnly) {
            // The spec's "In stock" toggle is a merchandising filter: physically
            // in stock right now. By design this excludes backorderable
            // (continue-policy) variants — they are still purchasable but not
            // "in stock", so they should not match this filter. (Contrast with
            // the product card's "Sold out" badge, which only flags deny-policy
            // variants — see ProductCardPresenter::isSoldOut.) available =
            // quantity_on_hand - quantity_reserved (computed on the model;
            // no column), expressed inline for the SQL filter.
            $query->whereHas('variants.inventoryItem', fn (Builder $q) => $q->whereRaw('quantity_on_hand - quantity_reserved > 0'));
        }

        return $this->applySort($query);
    }

    /**
     * Apply the selected sort order to the query.
     *
     * Price sorts order by each product's lowest variant price via a correlated
     * subquery so products with multiple variants sort by their cheapest SKU.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applySort(Builder $query): Builder
    {
        // The collection products relationship orders by pivot position by
        // default; drop that for explicit sorts so price/newest take effect.
        if ($this->sort !== 'featured') {
            $query->reorder();
        }

        $lowestPrice = ProductVariant::query()
            ->selectRaw('MIN(price_amount)')
            ->whereColumn('product_variants.product_id', 'products.id');

        return match ($this->sort) {
            'price-asc' => $query->orderBy($lowestPrice, 'asc'),
            'price-desc' => $query->orderByDesc($lowestPrice),
            'newest' => $query->orderByDesc('products.created_at'),
            default => $query->orderBy('collection_products.position'),
        };
    }

    /**
     * Distinct non-null values of a product column within this collection, for
     * building dynamic filter checkbox lists.
     *
     * @return list<string>
     */
    private function availableValues(string $column): array
    {
        return $this->collection->products()
            ->getQuery()
            ->published()
            ->whereNotNull("products.{$column}")
            ->distinct()
            ->orderBy("products.{$column}")
            ->pluck("products.{$column}")
            ->all();
    }

    private function hasActiveFilters(): bool
    {
        return $this->inStockOnly
            || ($this->minPrice !== null && $this->minPrice !== '')
            || ($this->maxPrice !== null && $this->maxPrice !== '')
            || $this->types !== []
            || $this->vendors !== [];
    }
}
