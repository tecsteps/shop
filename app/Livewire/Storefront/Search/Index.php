<?php

namespace App\Livewire\Storefront\Search;

use App\Livewire\Storefront\Concerns\QuickAddsProducts;
use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends StorefrontComponent
{
    use QuickAddsProducts, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    #[Url(as: 'sort', except: 'relevance')]
    public string $sort = 'relevance';

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

    public function updated(string $property): void
    {
        if (in_array($property, ['query', 'sort', 'inStock', 'minPrice', 'maxPrice', 'types', 'vendors'], true)) {
            $this->resetPage();
        }
    }

    public function searchNow(): void
    {
        $this->query = trim($this->query);
        $this->validate(['query' => ['required', 'string', 'max:200']]);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('sort', 'inStock', 'minPrice', 'maxPrice', 'types', 'vendors');
        $this->resetPage();
    }

    #[Computed]
    public function productTypes(): array
    {
        return Product::query()->where('store_id', $this->currentStore()->getKey())->where('status', 'active')->whereNotNull('product_type')->distinct()->orderBy('product_type')->pluck('product_type')->all();
    }

    #[Computed]
    public function availableVendors(): array
    {
        return Product::query()->where('store_id', $this->currentStore()->getKey())->where('status', 'active')->whereNotNull('vendor')->distinct()->orderBy('vendor')->pluck('vendor')->all();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->sort !== 'relevance' || $this->inStock || $this->minPrice !== '' || $this->maxPrice !== '' || $this->types !== [] || $this->vendors !== [];
    }

    public function render(): View
    {
        $results = $this->results();

        return $this->storefront(
            view('storefront.search.index', compact('results')),
            'Search results - '.$this->currentStore()->name,
            'Search products from '.$this->currentStore()->name.'.',
        );
    }

    private function results(): LengthAwarePaginator
    {
        if (trim($this->query) === '') {
            return Product::query()->whereRaw('1 = 0')->paginate(12);
        }

        $search = app(SearchService::class)->search($this->currentStore(), trim($this->query), [], 500);
        $products = new EloquentCollection($search->items());
        $products->loadMissing(['variants.inventoryItem', 'media']);

        $products = $products
            ->when($this->types !== [], fn ($items) => $items->whereIn('product_type', $this->types))
            ->when($this->vendors !== [], fn ($items) => $items->whereIn('vendor', $this->vendors))
            ->filter(function (Product $product): bool {
                $variants = $product->variants;
                if ($this->inStock && ! $variants->contains(function ($variant): bool {
                    $inventory = $variant->inventoryItem;

                    return ! $inventory
                        || ($inventory->policy instanceof \BackedEnum ? $inventory->policy->value : $inventory->policy) === 'continue'
                        || ((int) $inventory->quantity_on_hand - (int) $inventory->quantity_reserved) > 0;
                })) {
                    return false;
                }

                $minimum = $this->minPrice === '' ? null : (int) round((float) $this->minPrice * 100);
                $maximum = $this->maxPrice === '' ? null : (int) round((float) $this->maxPrice * 100);

                return $variants->contains(fn ($variant): bool => ($minimum === null || (int) $variant->price_amount >= $minimum)
                    && ($maximum === null || (int) $variant->price_amount <= $maximum));
            })
            ->values();

        if ($this->sort === 'price_asc') {
            $products = $products->sortBy(fn (Product $product): int => (int) $product->variants->min('price_amount'))->values();
        } elseif ($this->sort === 'price_desc') {
            $products = $products->sortByDesc(fn (Product $product): int => (int) $product->variants->max('price_amount'))->values();
        } elseif ($this->sort === 'newest') {
            $products = $products->sortByDesc('published_at')->values();
        } elseif ($this->sort === 'best_selling') {
            $products->loadCount('orderLines');
            $products = $products->sortByDesc('order_lines_count')->values();
        }

        $page = max(1, LengthAwarePaginator::resolveCurrentPage());

        return new LengthAwarePaginator(
            $products->forPage($page, 12)->values(),
            $products->count(),
            12,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
