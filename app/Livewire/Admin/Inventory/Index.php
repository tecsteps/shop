<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

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
        InventoryItem::withoutGlobalScopes()
            ->findOrFail($itemId)
            ->update(['quantity_on_hand' => max(0, $quantity)]);

        $this->dispatch('toast', type: 'success', message: 'Inventory updated.');
    }

    public function getInventoryItemsProperty()
    {
        $query = InventoryItem::withoutGlobalScopes()
            ->where('inventory_items.store_id', session('store_id'))
            ->join('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select('inventory_items.*', 'products.title as product_title', 'product_variants.sku', 'product_variants.option1', 'product_variants.option2', 'product_variants.option3');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('products.title', 'like', "%{$this->search}%")
                    ->orWhere('product_variants.sku', 'like', "%{$this->search}%");
            });
        }

        if ($this->stockFilter === 'in_stock') {
            $query->where('inventory_items.quantity_on_hand', '>', 0);
        } elseif ($this->stockFilter === 'low_stock') {
            $query->where('inventory_items.quantity_on_hand', '>', 0)
                ->where('inventory_items.quantity_on_hand', '<=', 10);
        } elseif ($this->stockFilter === 'out_of_stock') {
            $query->where('inventory_items.quantity_on_hand', '<=', 0);
        }

        return $query->orderBy('products.title')->paginate(20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.inventory.index', [
            'inventoryItems' => $this->inventoryItems,
        ]);
    }
}
