<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Inventory')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $stockFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $store = app('current_store');
        InventoryItem::where('id', $itemId)
            ->where('store_id', $store->id)
            ->update(['quantity_on_hand' => max(0, $quantity)]);

        $this->dispatch('toast', type: 'success', message: 'Quantity updated.');
    }

    /**
     * @return LengthAwarePaginator<InventoryItem>
     */
    #[Computed]
    public function inventoryItems(): LengthAwarePaginator
    {
        $store = app('current_store');

        return InventoryItem::query()
            ->where('inventory_items.store_id', $store->id)
            ->join('product_variants', 'inventory_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->when($this->search, fn ($q) => $q->where(function ($q): void {
                $q->where('products.title', 'like', "%{$this->search}%")
                    ->orWhere('product_variants.sku', 'like', "%{$this->search}%");
            }))
            ->when($this->stockFilter !== 'all', function ($q): void {
                match ($this->stockFilter) {
                    'in_stock' => $q->where('inventory_items.quantity_on_hand', '>', 0),
                    'low_stock' => $q->whereBetween('inventory_items.quantity_on_hand', [1, 10]),
                    'out_of_stock' => $q->where('inventory_items.quantity_on_hand', '<=', 0),
                    default => null,
                };
            })
            ->select('inventory_items.*', 'products.title as product_title', 'product_variants.sku')
            ->orderBy('products.title')
            ->paginate(20);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.inventory.index');
    }
}
