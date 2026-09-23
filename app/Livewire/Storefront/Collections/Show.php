<?php

namespace App\Livewire\Storefront\Collections;

use App\Livewire\Storefront\Concerns\DispatchesStorefrontAnalytics;
use App\Models\Collection;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use DispatchesStorefrontAnalytics;
    use WithPagination;

    public string $handle = '';

    public string $sort = 'featured';

    public bool $inStock = false;

    public string $minPrice = '';

    public string $maxPrice = '';

    /** @var list<string> */
    public array $selectedTypes = [];

    /** @var list<string> */
    public array $selectedVendors = [];

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['sort', 'inStock', 'minPrice', 'maxPrice', 'selectedTypes', 'selectedVendors'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['inStock', 'minPrice', 'maxPrice', 'selectedTypes', 'selectedVendors']);
        $this->resetPage();
    }

    public function removeFilter(string $key): void
    {
        [$type, $value] = array_pad(explode(':', $key, 2), 2, '');

        match ($type) {
            'type' => $this->selectedTypes = array_values(array_diff($this->selectedTypes, [$value])),
            'vendor' => $this->selectedVendors = array_values(array_diff($this->selectedVendors, [$value])),
            'stock' => $this->inStock = false,
            'min' => $this->minPrice = '',
            'max' => $this->maxPrice = '',
            default => null,
        };

        $this->resetPage();
    }

    public function quickAdd(int $variantId, CartService $carts): void
    {
        $variant = ProductVariant::query()->whereHas('product', fn ($query) => $query->where('status', 'active')->whereNotNull('published_at'))->findOrFail($variantId);
        $carts->add($carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user()), $variant, 1);
        $this->dispatchStorefrontAnalytics('add_to_cart', [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'price_amount' => (int) $variant->price_amount,
            'currency' => $variant->currency,
        ]);
        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }

    public function render(): mixed
    {
        $collection = Collection::query()->where('handle', $this->handle)->where('status', 'active')->firstOrFail();
        $baseQuery = $collection->products()->where('status', 'active')->whereNotNull('published_at');
        $productTypes = (clone $baseQuery)->whereNotNull('product_type')->distinct()->orderBy('product_type')->pluck('product_type')->all();
        $vendors = (clone $baseQuery)->whereNotNull('vendor')->distinct()->orderBy('vendor')->pluck('vendor')->all();
        $products = (clone $baseQuery)->with(['variants.inventoryItem', 'media']);

        if ($this->selectedTypes !== []) {
            $products->whereIn('product_type', $this->selectedTypes);
        }

        if ($this->selectedVendors !== []) {
            $products->whereIn('vendor', $this->selectedVendors);
        }

        if ($this->inStock) {
            $products->whereHas('variants.inventoryItem', fn ($query) => $query->where(function ($availability): void {
                $availability->where('policy', 'continue')->orWhereRaw('quantity_on_hand > quantity_reserved');
            }));
        }

        $products->when($this->minPrice !== '', fn ($query) => $query->whereHas('variants', fn ($variants) => $variants->where('price_amount', '>=', $this->minorUnits($this->minPrice))));
        $products->when($this->maxPrice !== '', fn ($query) => $query->whereHas('variants', fn ($variants) => $variants->where('price_amount', '<=', $this->minorUnits($this->maxPrice))));

        match ($this->sort) {
            'price_asc' => $products->withMin('variants', 'price_amount')->orderBy('variants_min_price_amount'),
            'price_desc' => $products->withMin('variants', 'price_amount')->orderByDesc('variants_min_price_amount'),
            'newest' => $products->latest('published_at'),
            'best_selling' => $products->orderByDesc('id'),
            default => $products->orderByPivot('position')->orderBy('title'),
        };
        $activeFilters = $this->activeFilters();

        return view('storefront.collection', [
            'collection' => $collection,
            'products' => $products->paginate(12),
            'productTypes' => $productTypes,
            'vendors' => $vendors,
            'activeFilters' => $activeFilters,
        ])->layout('storefront.layouts.app');
    }

    /** @return list<array{key: string, label: string}> */
    private function activeFilters(): array
    {
        $filters = [];

        foreach ($this->selectedTypes as $type) {
            $filters[] = ['key' => 'type:'.$type, 'label' => $type];
        }

        foreach ($this->selectedVendors as $vendor) {
            $filters[] = ['key' => 'vendor:'.$vendor, 'label' => $vendor];
        }

        if ($this->inStock) {
            $filters[] = ['key' => 'stock', 'label' => 'In stock'];
        }

        if ($this->minPrice !== '') {
            $filters[] = ['key' => 'min', 'label' => 'Min '.$this->minPrice];
        }

        if ($this->maxPrice !== '') {
            $filters[] = ['key' => 'max', 'label' => 'Max '.$this->maxPrice];
        }

        return $filters;
    }

    private function minorUnits(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
