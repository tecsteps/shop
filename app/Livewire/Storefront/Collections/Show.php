<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    #[Url]
    public bool $inStock = false;

    #[Url]
    public ?int $priceMin = null;

    #[Url]
    public ?int $priceMax = null;

    /** @var array<int, string> */
    #[Url]
    public array $productTypes = [];

    /** @var array<int, string> */
    #[Url]
    public array $vendors = [];

    #[Url]
    public string $sort = 'featured';

    public function mount(string $handle): void
    {
        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->firstOr(fn () => abort(404));
    }

    public function clearFilters(): void
    {
        $this->reset(['inStock', 'priceMin', 'priceMax', 'productTypes', 'vendors']);
        $this->resetPage();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function availableProductTypes(): SupportCollection
    {
        return $this->collection->products()->distinct()->pluck('product_type')->filter()->sort()->values();
    }

    #[Computed]
    public function availableVendors(): SupportCollection
    {
        return $this->collection->products()->distinct()->pluck('vendor')->filter()->sort()->values();
    }

    public function hasActiveFilters(): bool
    {
        return $this->inStock || $this->priceMin || $this->priceMax || $this->productTypes !== [] || $this->vendors !== [];
    }

    public function render()
    {
        $query = $this->collection->products()
            ->with(['variants.inventoryItem', 'media'])
            ->where('status', ProductStatus::Active);

        if ($this->productTypes !== []) {
            $query->whereIn('product_type', $this->productTypes);
        }

        if ($this->vendors !== []) {
            $query->whereIn('vendor', $this->vendors);
        }

        if ($this->priceMin !== null || $this->priceMax !== null || $this->inStock) {
            $query->whereHas('variants', function (Builder $builder): void {
                if ($this->priceMin !== null) {
                    $builder->where('price_amount', '>=', $this->priceMin * 100);
                }

                if ($this->priceMax !== null) {
                    $builder->where('price_amount', '<=', $this->priceMax * 100);
                }

                if ($this->inStock) {
                    $builder->whereHas('inventoryItem', function (Builder $inventory): void {
                        $inventory->whereColumn('quantity_on_hand', '>', 'quantity_reserved')
                            ->orWhere('policy', 'continue');
                    });
                }
            });
        }

        if ($this->sort === 'newest') {
            $query->reorder('published_at', 'desc');
        }

        // Manual (position) ordering is applied by the relation by default.
        $products = $query->paginate(12)->withQueryString();

        if (in_array($this->sort, ['price_asc', 'price_desc'], true)) {
            $items = $products->getCollection()->sortBy(function (Product $product): int {
                $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

                return $variant?->price_amount ?? 0;
            }, descending: $this->sort === 'price_desc')->values();

            $products->setCollection($items);
        }

        return view('livewire.storefront.collections.show', ['products' => $products])
            ->layout('layouts.storefront')
            ->title($this->collection->title.' - '.app('current_store')->name);
    }
}
