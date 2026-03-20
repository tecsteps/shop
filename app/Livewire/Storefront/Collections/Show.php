<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    public string $sort = 'featured';

    public string $availability = '';

    public string $productType = '';

    public string $vendor = '';

    public ?int $priceMin = null;

    public ?int $priceMax = null;

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

    public function updatedAvailability(): void
    {
        $this->resetPage();
    }

    public function updatedProductType(): void
    {
        $this->resetPage();
    }

    public function updatedVendor(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['availability', 'productType', 'vendor', 'priceMin', 'priceMax']);
        $this->resetPage();
    }

    public function render(): mixed
    {
        $query = $this->collection->products()->where('products.status', 'active');

        if ($this->productType) {
            $query->where('products.product_type', $this->productType);
        }

        if ($this->vendor) {
            $query->where('products.vendor', $this->vendor);
        }

        $query = match ($this->sort) {
            'price-asc' => $query->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->orderBy('product_variants.price_amount', 'asc')
                ->select('products.*')
                ->distinct(),
            'price-desc' => $query->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->orderBy('product_variants.price_amount', 'desc')
                ->select('products.*')
                ->distinct(),
            'newest' => $query->orderBy('products.created_at', 'desc'),
            default => $query->orderBy('collection_products.position'),
        };

        return view('livewire.storefront.collections.show', [
            'products' => $query->paginate(24),
        ])->layout('layouts::storefront');
    }
}
