<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public string $handle;

    public string $sort = 'featured';

    public bool $inStock = false;

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    /**
     * @var array<int, string>
     */
    public array $types = [];

    /**
     * @var array<int, string>
     */
    public array $vendors = [];

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->inStock = false;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->types = [];
        $this->vendors = [];
        $this->resetPage();
    }

    public function collection(): Collection
    {
        return Collection::query()
            ->where('handle', $this->handle)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function products(): LengthAwarePaginator
    {
        $collection = $this->collection();

        return $collection->products()
            ->with(['variants.inventoryItem'])
            ->withCount('variants')
            ->where('products.status', 'active')
            ->whereNotNull('products.published_at')
            ->when($this->inStock, function (Builder $query): void {
                $query->whereHas('variants.inventoryItem', function (Builder $query): void {
                    $query
                        ->where('policy', 'continue')
                        ->orWhereColumn('quantity_on_hand', '>', 'quantity_reserved');
                });
            })
            ->when($this->types !== [], fn (Builder $query) => $query->whereIn('product_type', $this->types))
            ->when($this->vendors !== [], fn (Builder $query) => $query->whereIn('vendor', $this->vendors))
            ->when($this->minPrice !== null, function (Builder $query): void {
                $query->whereHas('variants', fn (Builder $query) => $query->where('price_amount', '>=', $this->minPrice * 100));
            })
            ->when($this->maxPrice !== null, function (Builder $query): void {
                $query->whereHas('variants', fn (Builder $query) => $query->where('price_amount', '<=', $this->maxPrice * 100));
            })
            ->when($this->sort === 'price_asc', fn (Builder $query) => $query->orderBy(ProductVariant::select('price_amount')->whereColumn('product_id', 'products.id')->orderBy('price_amount')->limit(1)))
            ->when($this->sort === 'price_desc', fn (Builder $query) => $query->orderByDesc(ProductVariant::select('price_amount')->whereColumn('product_id', 'products.id')->orderBy('price_amount')->limit(1)))
            ->when($this->sort === 'newest', fn (Builder $query) => $query->latest('products.created_at'))
            ->paginate(12);
    }

    public function productTypes(): SupportCollection
    {
        return $this->collection()->products()
            ->where('products.status', 'active')
            ->whereNotNull('products.published_at')
            ->whereNotNull('product_type')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type');
    }

    public function productVendors(): SupportCollection
    {
        return $this->collection()->products()
            ->where('products.status', 'active')
            ->whereNotNull('products.published_at')
            ->whereNotNull('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor');
    }

    public function render(): mixed
    {
        $collection = $this->collection();

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $this->products(),
            'productTypes' => $this->productTypes(),
            'productVendors' => $this->productVendors(),
        ])->layout('layouts.storefront', [
            'title' => $collection->title,
        ]);
    }
}
