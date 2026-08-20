<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection as ProductCollection;
use Livewire\Component;

class Show extends Component
{
    public ProductCollection $collection;

    public string $sort = 'featured';

    public bool $inStock = false;

    public function mount(string $handle): void
    {
        $this->collection = ProductCollection::query()->where('handle', $handle)->with(['products.variants.inventory', 'products.media'])->firstOrFail();
    }

    public function render(): mixed
    {
        $products = $this->collection->products->filter(fn ($product): bool => $product->status->value === 'active' && (! $this->inStock || $product->variants->contains(fn ($variant): bool => $variant->availableQuantity() > 0)));

        if ($this->sort === 'price_asc') {
            $products = $products->sortBy(fn ($product): int => $product->defaultVariant()?->price_amount ?? 0);
        } elseif ($this->sort === 'price_desc') {
            $products = $products->sortByDesc(fn ($product): int => $product->defaultVariant()?->price_amount ?? 0);
        } elseif ($this->sort === 'newest') {
            $products = $products->sortByDesc('created_at');
        }

        return view('livewire.storefront.collections.show', ['products' => $products])->layout('layouts.storefront');
    }

    public function clearFilters(): void
    {
        $this->inStock = false;
        $this->sort = 'featured';
    }
}
