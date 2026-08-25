<?php

namespace App\Livewire\Storefront\Collections;

use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Collection;
use App\Models\OrderLine;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use InteractsWithStore;

    public string $handle = '';

    /** @var list<string> */
    public array $vendors = [];

    /** @var list<string> */
    public array $types = [];

    public ?int $priceMin = null;

    public ?int $priceMax = null;

    public bool $inStockOnly = false;

    public string $sort = 'featured';

    public int $page = 1;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
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

    public function quickAdd(int $variantId): void
    {
        try {
            app(\App\Services\CartService::class)->addLine($this->sessionCart(), $variantId, 1);
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
    public function collection(): Collection
    {
        return Collection::where('handle', $this->handle)
            ->where('status', 'active')
            ->firstOrFail();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $perPage = max(1, (int) $this->themeSetting('products_per_page', 12));

        $query = $this->collection->products()
            ->where('products.status', 'active')
            ->whereNotNull('products.published_at')
            ->with([
                'variants.inventoryItem',
                'variants.optionValues',
                'media' => fn ($media) => $media->where('status', 'ready')->orderBy('position'),
            ]);

        if ($this->vendors !== []) {
            $query->whereIn('products.vendor', $this->vendors);
        }

        if ($this->types !== []) {
            $query->whereIn('products.product_type', $this->types);
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
        return $this->collection->products()
            ->where('products.status', 'active')
            ->distinct()
            ->pluck('products.vendor')
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
        return $this->collection->products()
            ->where('products.status', 'active')
            ->distinct()
            ->pluck('products.product_type')
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
                $query->orderByDesc('products.created_at');

                break;
            case 'best_selling':
                $query->orderByDesc(
                    OrderLine::selectRaw('COALESCE(SUM(order_lines.quantity), 0)')
                        ->whereColumn('order_lines.product_id', 'products.id'),
                );

                break;
            default:
                $query->orderByPivot('position');
        }
    }
}
