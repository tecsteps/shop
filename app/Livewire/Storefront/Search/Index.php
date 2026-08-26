<?php

namespace App\Livewire\Storefront\Search;

use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use InteractsWithStore;

    public string $query = '';

    /** @var list<string> */
    public array $vendors = [];

    /** @var list<string> */
    public array $types = [];

    public ?int $priceMin = null;

    public ?int $priceMax = null;

    public bool $inStockOnly = false;

    public string $sort = 'relevance';

    public int $page = 1;

    public function mount(): void
    {
        $this->query = trim((string) request('q', ''));
    }

    /**
     * Reset pagination whenever a filter or sort changes.
     */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->page = 1;
        }
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Re-run the search with the current query (deferred wire:model syncs first).
     */
    public function setQuery(): void
    {
        $this->page = 1;
    }

    public function quickAdd(int $variantId): void
    {
        try {
            app(CartService::class)->addLine($this->sessionCart(), $variantId, 1);
        } catch (InsufficientInventoryException) {
            $this->dispatch('storefront-toast', type: 'error', message: 'This product is currently out of stock');

            return;
        }

        $cart = $this->sessionCart()->fresh()->load('lines');

        $this->dispatch('cart-updated', cartId: $cart->id, itemCount: (int) $cart->lines->sum('quantity'));
    }

    public function clearFilters(): void
    {
        $this->vendors = [];
        $this->types = [];
        $this->priceMin = null;
        $this->priceMax = null;
        $this->inStockOnly = false;
        $this->page = 1;
    }

    public function removeFilter(string $type, string $value): void
    {
        match ($type) {
            'vendor' => $this->vendors = array_values(array_diff($this->vendors, [$value])),
            'type' => $this->types = array_values(array_diff($this->types, [$value])),
            'price_min' => $this->priceMin = null,
            'price_max' => $this->priceMax = null,
            'in_stock' => $this->inStockOnly = false,
            default => null,
        };

        $this->page = 1;
    }

    #[Computed]
    public function results(): LengthAwarePaginator
    {
        $perPage = max(1, (int) $this->themeSetting('products_per_page', 12));
        $productIds = $this->matchedProductIds;

        $query = Product::where('status', 'active')
            ->whereNotNull('published_at')
            ->with([
                'variants.inventoryItem',
                'variants.optionValues',
                'media' => fn ($media) => $media->where('status', 'ready')->orderBy('position'),
            ]);

        if ($productIds === []) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereIn('id', $productIds);
        }

        if ($this->vendors !== []) {
            $query->whereIn('vendor', $this->vendors);
        }

        if ($this->types !== []) {
            $query->whereIn('product_type', $this->types);
        }

        if ($this->priceMin !== null) {
            $query->whereHas('variants', fn ($variants) => $variants
                ->where('status', 'active')
                ->where('price_amount', '>=', $this->priceMin));
        }

        if ($this->priceMax !== null) {
            $query->whereHas('variants', fn ($variants) => $variants
                ->where('status', 'active')
                ->where('price_amount', '<=', $this->priceMax));
        }

        if ($this->inStockOnly) {
            $query->whereHas('variants', fn ($variants) => $variants
                ->where('status', 'active')
                ->whereHas('inventoryItem', fn ($inventory) => $inventory->where(function ($item) {
                    $item->where('policy', 'continue')
                        ->orWhereRaw('(quantity_on_hand - quantity_reserved) > 0');
                })));
        }

        $this->applySort($query);

        return $query->paginate($perPage, ['*'], 'page', $this->page);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function vendors(): array
    {
        if ($this->matchedProductIds === []) {
            return [];
        }

        return Product::whereIn('id', $this->matchedProductIds)
            ->where('status', 'active')
            ->distinct()
            ->pluck('vendor')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function types(): array
    {
        if ($this->matchedProductIds === []) {
            return [];
        }

        return Product::whereIn('id', $this->matchedProductIds)
            ->where('status', 'active')
            ->distinct()
            ->pluck('product_type')
            ->filter()
            ->values()
            ->all();
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return count($this->vendors) + count($this->types)
            + (int) ($this->priceMin !== null)
            + (int) ($this->priceMax !== null)
            + (int) $this->inStockOnly;
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function matchedProductIds(): array
    {
        $sanitized = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $this->query)) ?? '');

        if ($sanitized === '') {
            return [];
        }

        $tokens = array_values(array_filter(preg_split('/\s+/', $sanitized) ?: []));
        $last = array_pop($tokens).'*';
        $tokens[] = $last;
        $match = implode(' ', array_map(fn (string $token) => '"'.$token.'"', $tokens));

        // Mirrors App\Services\SearchService FTS matching (which does not support
        // the full filter/sort set required by the storefront search page).
        return DB::table('products_fts')
            ->where('store_id', $this->store()->id)
            ->whereRaw('products_fts MATCH ?', [$match])
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Product>  $query
     */
    private function applySort($query): void
    {
        switch ($this->sort) {
            case 'price_asc':
                $query->orderBy(
                    ProductVariant::select('price_amount')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where('status', 'active')
                        ->orderBy('price_amount')
                        ->limit(1),
                );

                break;
            case 'price_desc':
                $query->orderByDesc(
                    ProductVariant::select('price_amount')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where('status', 'active')
                        ->orderByDesc('price_amount')
                        ->limit(1),
                );

                break;
            case 'newest':
                $query->orderByDesc('created_at');

                break;
            case 'best_selling':
                $query->orderByDesc(
                    OrderLine::selectRaw('COALESCE(SUM(order_lines.quantity), 0)')
                        ->whereColumn('order_lines.product_id', 'products.id'),
                );

                break;
            default:
                $query->orderByDesc('created_at');
        }
    }
}
