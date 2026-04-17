<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    public string $sort = 'featured';

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    public bool $inStock = false;

    /**
     * @var array<int, string>
     */
    public array $selectedTypes = [];

    /**
     * @var array<int, string>
     */
    public array $selectedVendors = [];

    public function mount(string $handle): void
    {
        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->firstOrFail();
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

    public function updatedInStock(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedTypes(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedVendors(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->inStock = false;
        $this->selectedTypes = [];
        $this->selectedVendors = [];
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->inStock
            || $this->minPrice !== null
            || $this->maxPrice !== null
            || count($this->selectedTypes) > 0
            || count($this->selectedVendors) > 0;
    }

    public function render(): View
    {
        $query = $this->collection->products()
            ->where('products.status', ProductStatus::Active)
            ->with(['variants.inventoryItem', 'media']);

        if ($this->minPrice !== null) {
            $query->whereHas('variants', function ($q): void {
                $q->where('price_amount', '>=', $this->minPrice * 100);
            });
        }

        if ($this->maxPrice !== null) {
            $query->whereHas('variants', function ($q): void {
                $q->where('price_amount', '<=', $this->maxPrice * 100);
            });
        }

        if (count($this->selectedTypes) > 0) {
            $query->whereIn('product_type', $this->selectedTypes);
        }

        if (count($this->selectedVendors) > 0) {
            $query->whereIn('vendor', $this->selectedVendors);
        }

        $query = match ($this->sort) {
            'price-asc' => $query->orderBy(
                \App\Models\ProductVariant::select('price_amount')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_default', true)
                    ->limit(1),
                'asc'
            ),
            'price-desc' => $query->orderBy(
                \App\Models\ProductVariant::select('price_amount')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_default', true)
                    ->limit(1),
                'desc'
            ),
            'newest' => $query->orderBy('products.created_at', 'desc'),
            default => $query->orderBy('collection_products.position'),
        };

        $products = $query->paginate(12);

        $allProducts = $this->collection->products()
            ->where('products.status', ProductStatus::Active);
        $productTypes = (clone $allProducts)->whereNotNull('product_type')->distinct()->pluck('product_type')->toArray();
        $vendors = (clone $allProducts)->whereNotNull('vendor')->distinct()->pluck('vendor')->toArray();

        return view('livewire.storefront.collections.show', [
            'products' => $products,
            'productTypes' => $productTypes,
            'vendors' => $vendors,
        ])->layout('storefront.layouts.app', [
            'title' => $this->collection->title,
        ]);
    }
}
