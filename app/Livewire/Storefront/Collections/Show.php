<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class Show extends Component
{
    use WithPagination;

    public string $handle;

    public string $sort = 'featured';

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $collection = Collection::query()
            ->where('handle', $handle)
            ->where('status', CollectionStatus::Active)
            ->first();

        if (! $collection) {
            abort(404);
        }
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $collection = Collection::query()
            ->where('handle', $this->handle)
            ->where('status', CollectionStatus::Active)
            ->first();

        if (! $collection) {
            abort(404);
        }

        $productsQuery = $collection->products()
            ->where('products.status', ProductStatus::Active)
            ->with('variants', 'media');

        $productsQuery = match ($this->sort) {
            'price-asc' => $productsQuery->join('product_variants', function ($join): void {
                $join->on('products.id', '=', 'product_variants.product_id')
                    ->where('product_variants.is_default', true);
            })->orderBy('product_variants.price_amount', 'asc')->select('products.*'),
            'price-desc' => $productsQuery->join('product_variants', function ($join): void {
                $join->on('products.id', '=', 'product_variants.product_id')
                    ->where('product_variants.is_default', true);
            })->orderBy('product_variants.price_amount', 'desc')->select('products.*'),
            'newest' => $productsQuery->orderBy('products.created_at', 'desc'),
            default => $productsQuery->orderByPivot('position'),
        };

        $products = $productsQuery->paginate(12);

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ]);
    }
}
