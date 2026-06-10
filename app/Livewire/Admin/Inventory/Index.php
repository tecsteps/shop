<?php

namespace App\Livewire\Admin\Inventory;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts, WithPagination;

    /**
     * Available quantity at or below this value counts as low stock.
     */
    public const int LOW_STOCK_THRESHOLD = 5;

    #[Url]
    public string $search = '';

    #[Url]
    public string $stockFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Inline on-hand quantity adjustment (spec 03 section 6). Inventory
     * adjustments follow the product update permission (owner/admin/staff).
     */
    public function updateQuantity(int $itemId, mixed $quantity): void
    {
        $item = InventoryItem::query()->with('variant.product')->findOrFail($itemId);

        $this->authorize('update', $item->variant->product);

        $quantity = max(0, (int) $quantity);

        $item->update(['quantity_on_hand' => $quantity]);

        $this->toast(__('Inventory updated.'));
    }

    /**
     * @return LengthAwarePaginator<int, InventoryItem>
     */
    #[Computed]
    public function inventoryItems(): LengthAwarePaginator
    {
        return InventoryItem::query()
            ->with('variant.product', 'variant.optionValues')
            ->whereHas('variant')
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query->whereHas('variant', fn ($query) => $query->where('sku', 'like', $search))
                        ->orWhereHas('variant.product', fn ($query) => $query->where('title', 'like', $search));
                });
            })
            ->when($this->stockFilter !== 'all', function ($query): void {
                match ($this->stockFilter) {
                    'in_stock' => $query->whereRaw('quantity_on_hand - quantity_reserved > 0'),
                    'low_stock' => $query
                        ->whereRaw('quantity_on_hand - quantity_reserved > 0')
                        ->whereRaw('quantity_on_hand - quantity_reserved <= ?', [self::LOW_STOCK_THRESHOLD]),
                    'out_of_stock' => $query->whereRaw('quantity_on_hand - quantity_reserved <= 0'),
                    default => null,
                };
            })
            ->orderBy('id')
            ->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.admin.inventory.index')->title(__('Inventory'));
    }
}
