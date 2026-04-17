<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
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
                ->where('status', CollectionStatus::Active)
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
                ->where('status', CollectionStatus::Active)
                ->first();

            if ($collection) {
                $query = $collection->products()
                    ->where('products.status', ProductStatus::Active)
                    ->with(['variants', 'media']);

                if ($this->minPrice !== null || $this->maxPrice !== null || in_array($this->sort, ['price_asc', 'price_desc'])) {
                    $query->joinSub(
                        \App\Models\ProductVariant::query()
                            ->selectRaw('product_id, MIN(price_amount) as min_price')
                            ->where('is_default', true)
                            ->groupBy('product_id'),
                        'default_prices',
                        'products.id',
                        '=',
                        'default_prices.product_id'
                    );
                }

                if ($this->minPrice !== null) {
                    $query->where('default_prices.min_price', '>=', $this->minPrice * 100);
                }

                if ($this->maxPrice !== null) {
                    $query->where('default_prices.min_price', '<=', $this->maxPrice * 100);
                }

                $query = match ($this->sort) {
                    'price_asc' => $query->orderBy('default_prices.min_price', 'asc'),
                    'price_desc' => $query->orderBy('default_prices.min_price', 'desc'),
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
