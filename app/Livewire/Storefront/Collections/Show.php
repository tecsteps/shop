<?php

namespace App\Livewire\Storefront\Collections;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use WithPagination;

    public string $handle = '';

    public string $collectionTitle = '';

    public string $collectionDescription = '';

    #[Url]
    public string $sort = 'featured';

    #[Url]
    public bool $inStock = false;

    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        if (class_exists(\App\Models\Collection::class)) {
            $collection = \App\Models\Collection::query()
                ->where('handle', $handle)
                ->where('status', 'active')
                ->first();

            if (! $collection) {
                abort(404);
            }

            $this->collectionTitle = $collection->title;
            $this->collectionDescription = $collection->description_html ?? '';
        }
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedInStock(): void
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

    public function clearFilters(): void
    {
        $this->inStock = false;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $products = collect();
        $totalProducts = 0;

        if (class_exists(\App\Models\Product::class) && class_exists(\App\Models\Collection::class)) {
            $collection = \App\Models\Collection::query()
                ->where('handle', $this->handle)
                ->where('status', 'active')
                ->first();

            if ($collection) {
                $query = $collection->products()
                    ->where('products.status', 'active');

                if ($this->minPrice !== null) {
                    $query->where('products.price_amount', '>=', $this->minPrice * 100);
                }

                if ($this->maxPrice !== null) {
                    $query->where('products.price_amount', '<=', $this->maxPrice * 100);
                }

                $query = match ($this->sort) {
                    'price_asc' => $query->orderBy('products.price_amount', 'asc'),
                    'price_desc' => $query->orderBy('products.price_amount', 'desc'),
                    'newest' => $query->orderBy('products.created_at', 'desc'),
                    default => $query->orderBy('products.title', 'asc'),
                };

                $products = $query->paginate(12);
                $totalProducts = $products->total();
            }
        }

        return view('livewire.storefront.collections.show', [
            'products' => $products,
            'totalProducts' => $totalProducts,
        ]);
    }
}
