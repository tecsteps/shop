<?php

namespace App\Livewire\Admin\Inventory;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /**
     * Available stock at or below this threshold counts as low stock.
     */
    private const LOW_STOCK_THRESHOLD = 5;

    public string $search = '';

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
     * Set the on-hand quantity of an inventory item (spec 03 §6 inline edit).
     */
    public function setQuantity(int $itemId, mixed $value): void
    {
        $item = $this->findItem($itemId);
        $this->authorize('update', $item->variant->product);

        $quantity = max(0, (int) $value);

        DB::transaction(function () use ($itemId, $quantity): void {
            InventoryItem::query()->lockForUpdate()->findOrFail($itemId)
                ->update(['quantity_on_hand' => $quantity]);
        });

        $this->dispatch('toast', type: 'success', message: 'Changes saved.');
    }

    /**
     * Adjust the on-hand quantity by a relative delta.
     */
    public function adjustQuantity(int $itemId, int $delta): void
    {
        $item = $this->findItem($itemId);
        $this->authorize('update', $item->variant->product);

        DB::transaction(function () use ($itemId, $delta): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($itemId);
            $locked->update(['quantity_on_hand' => max(0, $locked->quantity_on_hand + $delta)]);
        });

        $this->dispatch('toast', type: 'success', message: 'Changes saved.');
    }

    /**
     * Flip the oversell policy between deny and continue (spec 03 §6).
     */
    public function togglePolicy(int $itemId): void
    {
        $item = $this->findItem($itemId);
        $this->authorize('update', $item->variant->product);

        $item->update([
            'policy' => $item->policy === InventoryPolicy::Deny ? InventoryPolicy::Continue : InventoryPolicy::Deny,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Changes saved.');
    }

    public function render(): View
    {
        $items = $this->itemsQuery()
            ->with(['variant.product', 'variant.optionValues'])
            ->paginate(15);

        return view('livewire.admin.inventory.index', [
            'items' => $items,
        ])->layout('admin.layouts.app')->title('Inventory');
    }

    /**
     * Inventory items with search and stock-level filters (spec 03 §6).
     *
     * @return Builder<InventoryItem>
     */
    private function itemsQuery(): Builder
    {
        return InventoryItem::query()
            ->join('product_variants', 'inventory_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('inventory_items.*')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('product_variants.sku', 'like', $term)
                        ->orWhere('products.title', 'like', $term);
                });
            })
            ->when($this->stockFilter !== 'all', function (Builder $query): void {
                $available = '(inventory_items.quantity_on_hand - inventory_items.quantity_reserved)';

                match ($this->stockFilter) {
                    'in_stock' => $query->whereRaw("{$available} > ?", [self::LOW_STOCK_THRESHOLD]),
                    'low_stock' => $query->whereRaw("{$available} BETWEEN 1 AND ?", [self::LOW_STOCK_THRESHOLD]),
                    'out_of_stock' => $query->whereRaw("{$available} <= 0"),
                    default => null,
                };
            })
            ->orderBy('products.title')
            ->orderBy('product_variants.position');
    }

    /**
     * Find an inventory item, scoped to the current store by the global scope.
     */
    private function findItem(int $itemId): InventoryItem
    {
        return InventoryItem::query()->with('variant.product')->findOrFail($itemId);
    }
}
