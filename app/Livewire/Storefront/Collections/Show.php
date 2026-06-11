<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use App\Models\ProductVariant;
use App\Services\ThemeSettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
class Show extends Component
{
    use WithPagination;

    public Collection $collection;

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    #[Url(except: false)]
    public bool $inStock = false;

    #[Url(except: '')]
    public string $priceMin = '';

    #[Url(except: '')]
    public string $priceMax = '';

    /** @var list<string> */
    #[Url(except: [])]
    public array $productTypes = [];

    /** @var list<string> */
    #[Url(except: [])]
    public array $vendors = [];

    public function mount(string $handle): void
    {
        $this->collection = Collection::query()
            ->published()
            ->where('handle', $handle)
            ->firstOrFail();
    }

    /**
     * Reset pagination whenever a filter or the sort order changes.
     */
    public function updated(string $property): void
    {
        if (in_array(str($property)->before('.')->value(), ['sort', 'inStock', 'priceMin', 'priceMax', 'productTypes', 'vendors'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('inStock', 'priceMin', 'priceMax', 'productTypes', 'vendors');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->inStock
            || $this->priceMin !== ''
            || $this->priceMax !== ''
            || $this->productTypes !== []
            || $this->vendors !== [];
    }

    public function render(ThemeSettingsService $themeSettings): View
    {
        return view('livewire.storefront.collections.show', [
            'products' => $this->products((int) $themeSettings->get('products_per_page', 12)),
            'availableProductTypes' => $this->facetValues('product_type'),
            'availableVendors' => $this->facetValues('vendor'),
            'hasActiveFilters' => $this->hasActiveFilters(),
        ])->title($this->collection->title);
    }

    /**
     * @return LengthAwarePaginator<int, \App\Models\Product>
     */
    protected function products(int $perPage): LengthAwarePaginator
    {
        $query = $this->collection->products()
            ->published()
            ->with(['variants.inventoryItem', 'media'])
            ->reorder();

        if ($this->inStock) {
            $query->whereHas('variants.inventoryItem', function (Builder $inventory): void {
                $inventory->whereRaw('quantity_on_hand - quantity_reserved > 0');
            });
        }

        if ($this->priceMin !== '' && is_numeric($this->priceMin)) {
            $query->whereHas('variants', fn (Builder $variants) => $variants->where('price_amount', '>=', (int) round((float) $this->priceMin * 100)));
        }

        if ($this->priceMax !== '' && is_numeric($this->priceMax)) {
            $query->whereHas('variants', fn (Builder $variants) => $variants->where('price_amount', '<=', (int) round((float) $this->priceMax * 100)));
        }

        if ($this->productTypes !== []) {
            $query->whereIn('product_type', $this->productTypes);
        }

        if ($this->vendors !== []) {
            $query->whereIn('vendor', $this->vendors);
        }

        $defaultVariantPrice = ProductVariant::query()
            ->select('price_amount')
            ->whereColumn('product_id', 'products.id')
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->limit(1);

        match ($this->sort) {
            'price_asc' => $query->orderBy($defaultVariantPrice),
            'price_desc' => $query->orderByDesc($defaultVariantPrice),
            'newest' => $query->orderByDesc('products.created_at')->orderByDesc('products.id'),
            default => $query->orderByPivot('position'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Distinct facet values (product types or vendors) within the collection.
     *
     * @return list<string>
     */
    protected function facetValues(string $column): array
    {
        return $this->collection->products()
            ->published()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->reorder()
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
