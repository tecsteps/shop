<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    public string $sort = 'newest';

    public string $vendor = '';

    public ?int $priceMin = null;

    public ?int $priceMax = null;

    public function mount(string $handle): void
    {
        $collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->first();

        if (! $collection) {
            abort(404);
        }

        $this->collection = $collection;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedVendor(): void
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

    /**
     * @return array<int, string>
     */
    public function getVendorsProperty(): array
    {
        return $this->collection->products()
            ->where('status', ProductStatus::Active)
            ->whereNotNull('vendor')
            ->distinct()
            ->pluck('vendor')
            ->sort()
            ->values()
            ->all();
    }

    public function getProductsProperty(): LengthAwarePaginator
    {
        $query = $this->collection->products()
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->whereHas('variants', fn ($q) => $q->where('status', VariantStatus::Active))
            ->with([
                'variants' => fn ($q) => $q->where('status', VariantStatus::Active),
                'variants.inventoryItem',
                'media',
            ]);

        if ($this->vendor) {
            $query->where('products.vendor', $this->vendor);
        }

        if ($this->priceMin !== null) {
            $query->whereHas('variants', fn ($q) => $q->where('price_amount', '>=', $this->priceMin * 100));
        }

        if ($this->priceMax !== null) {
            $query->whereHas('variants', fn ($q) => $q->where('price_amount', '<=', $this->priceMax * 100));
        }

        $query = match ($this->sort) {
            'price-asc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.status = ?) ASC', [VariantStatus::Active->value]),
            'price-desc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.status = ?) DESC', [VariantStatus::Active->value]),
            'title-asc' => $query->orderBy('products.title'),
            default => $query->orderBy('products.created_at', 'desc'),
        };

        return $query->paginate(12);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.collections.show', [
            'products' => $this->products,
            'vendors' => $this->vendors,
        ])->layout('layouts.storefront', ['title' => $this->collection->title]);
    }
}
