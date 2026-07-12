<?php

namespace App\Livewire\Storefront\Collections;

use App\Livewire\Storefront\Concerns\QuickAddsProducts;
use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Show extends StorefrontComponent
{
    use QuickAddsProducts, WithPagination;

    public Collection $collection;

    #[Url(as: 'sort', except: 'featured')]
    public string $sort = 'featured';

    #[Url(as: 'in_stock', except: false)]
    public bool $inStock = false;

    #[Url(as: 'min_price', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max_price', except: '')]
    public string $maxPrice = '';

    /** @var array<int, string> */
    #[Url(as: 'type', except: [])]
    public array $types = [];

    /** @var array<int, string> */
    #[Url(as: 'vendor', except: [])]
    public array $vendors = [];

    public bool $filtersOpen = false;

    public function mount(string|Collection $handle): void
    {
        $this->collection = $handle instanceof Collection
            ? $handle
            : Collection::query()
                ->where('store_id', $this->currentStore()->getKey())
                ->where('handle', $handle)
                ->where('status', 'active')
                ->firstOrFail();

        abort_unless((int) $this->collection->store_id === (int) $this->currentStore()->getKey(), 404);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['sort', 'inStock', 'minPrice', 'maxPrice', 'types', 'vendors'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('inStock', 'minPrice', 'maxPrice', 'types', 'vendors');
        $this->resetPage();
    }

    #[Computed]
    public function productTypes(): array
    {
        return $this->baseProducts()->whereNotNull('product_type')->distinct()->orderBy('product_type')->pluck('product_type')->all();
    }

    #[Computed]
    public function availableVendors(): array
    {
        return $this->baseProducts()->whereNotNull('vendor')->distinct()->orderBy('vendor')->pluck('vendor')->all();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->inStock || $this->minPrice !== '' || $this->maxPrice !== '' || $this->types !== [] || $this->vendors !== [];
    }

    public function render(): View
    {
        $products = $this->filteredProducts()
            ->with(['variants.inventoryItem', 'media'])
            ->paginate(12);

        $description = trim(strip_tags((string) $this->collection->description_html));

        return $this->storefront(
            view('storefront.collections.show', compact('products')),
            $this->collection->title.' - '.$this->currentStore()->name,
            str($description)->limit(160)->toString(),
        );
    }

    private function baseProducts(): Builder
    {
        return Product::query()
            ->where('products.store_id', $this->currentStore()->getKey())
            ->where('products.status', 'active')
            ->whereNotNull('products.published_at')
            ->whereHas('collections', fn (Builder $query) => $query->whereKey($this->collection->getKey()));
    }

    private function filteredProducts(): Builder
    {
        $query = $this->baseProducts();

        $query->when($this->types !== [], fn (Builder $builder) => $builder->whereIn('product_type', $this->types));
        $query->when($this->vendors !== [], fn (Builder $builder) => $builder->whereIn('vendor', $this->vendors));

        if ($this->inStock) {
            $query->whereHas('variants.inventoryItem', fn (Builder $builder) => $builder
                ->where('policy', 'continue')
                ->orWhereColumn('quantity_on_hand', '>', 'quantity_reserved'));
        }

        if ($this->minPrice !== '') {
            $query->whereHas('variants', fn (Builder $builder) => $builder->where('price_amount', '>=', (int) round((float) $this->minPrice * 100)));
        }

        if ($this->maxPrice !== '') {
            $query->whereHas('variants', fn (Builder $builder) => $builder->where('price_amount', '<=', (int) round((float) $this->maxPrice * 100)));
        }

        return match ($this->sort) {
            'price_asc' => $query->orderByRaw('(select min(price_amount) from product_variants where product_variants.product_id = products.id) asc'),
            'price_desc' => $query->orderByRaw('(select max(price_amount) from product_variants where product_variants.product_id = products.id) desc'),
            'newest' => $query->latest('published_at'),
            'best_selling' => $query->withCount('orderLines')->orderByDesc('order_lines_count'),
            default => $query->orderBy('products.title'),
        };
    }
}
