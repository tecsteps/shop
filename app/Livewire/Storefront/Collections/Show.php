<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\VariantStatus;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Collection;
use App\Services\StorefrontCatalog;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use ResolvesStore, WithPagination;

    public string $handle = '';

    #[Url]
    public string $sort = 'featured';

    #[Url]
    public bool $inStock = false;

    #[Url]
    public ?int $priceMin = null;

    #[Url]
    public ?int $priceMax = null;

    /** @var array<int, string> */
    #[Url]
    public array $vendors = [];

    /** @var array<int, string> */
    #[Url]
    public array $types = [];

    public bool $showMobileFilters = false;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedInStock(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
    }

    public function updatedVendors(): void
    {
        $this->resetPage();
    }

    public function updatedTypes(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['inStock', 'priceMin', 'priceMax', 'vendors', 'types']);
        $this->resetPage();
    }

    public function removeVendor(string $vendor): void
    {
        $this->vendors = array_values(array_diff($this->vendors, [$vendor]));
        $this->resetPage();
    }

    public function removeType(string $type): void
    {
        $this->types = array_values(array_diff($this->types, [$type]));
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->inStock || $this->priceMin !== null || $this->priceMax !== null || $this->vendors !== [] || $this->types !== [];
    }

    public function render()
    {
        $store = $this->store();

        $collection = Collection::query()
            ->where('store_id', $store->getKey())
            ->where('handle', $this->handle)
            ->where('status', CollectionStatus::Active)
            ->first();

        abort_if($collection === null, 404);

        $catalog = app(StorefrontCatalog::class);
        $query = $catalog->visibleProducts($store)
            ->whereHas('collections', fn ($query) => $query->where('collections.id', $collection->getKey()));

        $this->applyFilters($query);
        $this->applySort($query);

        $products = $catalog->withStorefrontRelations($query)->paginate(12);

        $baseQuery = $catalog->visibleProducts($store)
            ->whereHas('collections', fn ($query) => $query->where('collections.id', $collection->getKey()));

        return view('livewire.storefront.collections.show', [
            'store' => $store,
            'collection' => $collection,
            'products' => $products,
            'availableVendors' => (clone $baseQuery)->whereNotNull('vendor')->distinct()->orderBy('vendor')->pluck('vendor')->all(),
            'availableTypes' => (clone $baseQuery)->whereNotNull('product_type')->distinct()->orderBy('product_type')->pluck('product_type')->all(),
        ])->layout('storefront.layouts.app', [
            'title' => $collection->title.' - '.$store->name,
            'metaDescription' => trim(strip_tags((string) $collection->description_html)) ?: $collection->title,
        ]);
    }

    protected function applyFilters(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if ($this->vendors !== []) {
            $query->whereIn('vendor', $this->vendors);
        }

        if ($this->types !== []) {
            $query->whereIn('product_type', $this->types);
        }

        if ($this->priceMin !== null || $this->priceMax !== null) {
            $min = $this->priceMin;
            $max = $this->priceMax;

            $query->whereHas('productVariants', function ($query) use ($min, $max): void {
                $query->where('status', VariantStatus::Active);

                if ($min !== null) {
                    $query->where('price_amount', '>=', $min * 100);
                }

                if ($max !== null) {
                    $query->where('price_amount', '<=', $max * 100);
                }
            });
        }

        if ($this->inStock) {
            $query->whereHas('productVariants', function ($query): void {
                $query->where('status', VariantStatus::Active)->where(function ($query): void {
                    $query->whereDoesntHave('inventoryItem')->orWhereHas('inventoryItem', function ($query): void {
                        $query->where('policy', \App\Enums\InventoryPolicy::Continue)
                            ->orWhereRaw('quantity_on_hand - quantity_reserved > 0');
                    });
                });
            });
        }
    }

    protected function applySort(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $active = VariantStatus::Active->value;

        match ($this->sort) {
            'price_asc' => $query->orderByRaw(
                "(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id AND status = '{$active}') ASC"
            ),
            'price_desc' => $query->orderByRaw(
                "(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id AND status = '{$active}') DESC"
            ),
            'newest' => $query->orderByDesc('created_at'),
            'best_selling' => $query->orderByRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_lines WHERE order_lines.product_id = products.id) DESC'),
            default => $query->orderBy('id'),
        };
    }
}
