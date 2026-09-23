<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Services\CartService;
use App\Services\SearchService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $query = '';

    public string $sort = 'relevance';

    public bool $inStock = false;

    public string $minPrice = '';

    public string $maxPrice = '';

    /** @var list<string> */
    public array $selectedTypes = [];

    /** @var list<string> */
    public array $selectedVendors = [];

    public function mount(): void
    {
        $this->query = trim((string) request()->query('q', ''));
        $this->recordSearch();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['query', 'sort', 'inStock', 'minPrice', 'maxPrice', 'selectedTypes', 'selectedVendors'], true)) {
            $this->resetPage();
        }

        if ($property === 'query') {
            $this->recordSearch();
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
        $variant = ProductVariant::query()->whereHas('product', fn ($builder) => $builder->where('status', 'active')->whereNotNull('published_at'))->findOrFail($variantId);
        $carts->add($carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user()), $variant, 1);
        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }

    public function render(SearchService $search): mixed
    {
        $active = Product::query()->where('status', 'active')->whereNotNull('published_at');
        $productTypes = (clone $active)->whereNotNull('product_type')->distinct()->orderBy('product_type')->pluck('product_type')->all();
        $vendors = (clone $active)->whereNotNull('vendor')->distinct()->orderBy('vendor')->pluck('vendor')->all();
        $products = (clone $active)->with(['variants.inventoryItem', 'media']);

        $rankedProductIds = [];

        if ($this->query !== '') {
            $escaped = addcslashes($this->query, '%_\\');
            $expression = $search->fullTextExpression((int) app('current_store')->id, $this->query);
            $rankedProductIds = $search->rankedProductIds((int) app('current_store')->id, $expression, 5000);
            $products->where(function ($builder) use ($rankedProductIds, $expression, $escaped): void {
                if ($rankedProductIds !== []) {
                    $builder->whereIn('id', $rankedProductIds)
                        ->orWhere('title', 'like', '%'.$escaped.'%')
                        ->orWhere('description_html', 'like', '%'.$escaped.'%')
                        ->orWhere('vendor', 'like', '%'.$escaped.'%')
                        ->orWhere('tags', 'like', '%'.$escaped.'%');
                } elseif ($expression === '') {
                    $builder->whereRaw('1 = 0');
                } else {
                    $builder->where('title', 'like', '%'.$escaped.'%')
                        ->orWhere('description_html', 'like', '%'.$escaped.'%')
                        ->orWhere('vendor', 'like', '%'.$escaped.'%')
                        ->orWhere('tags', 'like', '%'.$escaped.'%');
                }
            });
        }

        if ($this->selectedTypes !== []) {
            $products->whereIn('product_type', $this->selectedTypes);
        }

        if ($this->selectedVendors !== []) {
            $products->whereIn('vendor', $this->selectedVendors);
        }

        if ($this->inStock) {
            $products->whereHas('variants.inventoryItem', fn ($builder) => $builder->where(function ($availability): void {
                $availability->where('policy', 'continue')->orWhereRaw('quantity_on_hand > quantity_reserved');
            }));
        }

        $products->when($this->minPrice !== '', fn ($builder) => $builder->whereHas('variants', fn ($variants) => $variants->where('price_amount', '>=', $this->minorUnits($this->minPrice))));
        $products->when($this->maxPrice !== '', fn ($builder) => $builder->whereHas('variants', fn ($variants) => $variants->where('price_amount', '<=', $this->minorUnits($this->maxPrice))));

        match ($this->sort) {
            'price_asc' => $products->withMin('variants', 'price_amount')->orderBy('variants_min_price_amount'),
            'price_desc' => $products->withMin('variants', 'price_amount')->orderByDesc('variants_min_price_amount'),
            'newest' => $products->latest('published_at'),
            default => $this->orderByRelevance($products, $rankedProductIds),
        };

        return view('storefront.search', [
            'query' => $this->query,
            'products' => $products->paginate(12),
            'productTypes' => $productTypes,
            'vendors' => $vendors,
            'activeFilters' => $this->activeFilters(),
        ])->layout('storefront.layouts.app');
    }

    private function recordSearch(): void
    {
        if (trim($this->query) !== '') {
            SearchQuery::create(['query' => mb_substr(trim($this->query), 0, 255), 'session_id' => session()->getId()]);
        }
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

    /** @param list<string> $rankedProductIds */
    private function orderByRelevance(\Illuminate\Database\Eloquent\Builder $query, array $rankedProductIds): void
    {
        $rankedProductIds = array_values(array_unique(array_map('intval', $rankedProductIds)));

        if ($rankedProductIds === []) {
            $query->orderBy('title');

            return;
        }

        $rankCases = collect($rankedProductIds)
            ->map(static fn (int $productId, int $position): string => "WHEN {$productId} THEN {$position}")
            ->implode(' ');

        $query->orderByRaw('CASE products.id '.$rankCases.' ELSE '.count($rankedProductIds).' END')
            ->orderBy('title');
    }
}
