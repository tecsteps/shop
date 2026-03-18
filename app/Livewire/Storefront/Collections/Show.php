<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    #[Url]
    public string $sort = 'featured';

    #[Url]
    public bool $inStock = false;

    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    #[Url]
    public array $productTypes = [];

    #[Url]
    public array $vendors = [];

    public function mount(string $handle): void
    {
        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->firstOrFail();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = $this->collection->products()
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media']);

        if ($this->inStock) {
            $query->whereHas('variants.inventoryItem', function ($q) {
                $q->whereColumn('quantity_on_hand', '>', 'quantity_reserved');
            });
        }

        if ($this->minPrice !== null) {
            $query->whereHas('variants', function ($q) {
                $q->where('is_default', true)->where('price_amount', '>=', $this->minPrice);
            });
        }

        if ($this->maxPrice !== null) {
            $query->whereHas('variants', function ($q) {
                $q->where('is_default', true)->where('price_amount', '<=', $this->maxPrice);
            });
        }

        if (! empty($this->productTypes)) {
            $query->whereIn('products.product_type', $this->productTypes);
        }

        if (! empty($this->vendors)) {
            $query->whereIn('products.vendor', $this->vendors);
        }

        $query = match ($this->sort) {
            'price-asc' => $query->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_default = 1 LIMIT 1) ASC'),
            'price-desc' => $query->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_default = 1 LIMIT 1) DESC'),
            'newest' => $query->orderBy('products.created_at', 'desc'),
            default => $query->orderBy('collection_products.position'),
        };

        return $query->paginate(12);
    }

    #[Computed]
    public function availableProductTypes(): array
    {
        return $this->collection->products()
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.product_type')
            ->distinct()
            ->pluck('products.product_type')
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function availableVendors(): array
    {
        return $this->collection->products()
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.vendor')
            ->distinct()
            ->pluck('products.vendor')
            ->sort()
            ->values()
            ->all();
    }

    public function clearFilters(): void
    {
        $this->reset(['inStock', 'minPrice', 'maxPrice', 'productTypes', 'vendors']);
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.storefront.collections.show')
            ->layout('storefront.layouts.app', ['title' => $this->collection->title]);
    }
}
