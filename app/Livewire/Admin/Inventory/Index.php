<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
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

    /** @var array<int, int> */
    public array $editingQuantities = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updateQuantity(int $itemId): void
    {
        $store = app('current_store');

        $item = InventoryItem::query()
            ->where('store_id', $store->id)
            ->findOrFail($itemId);

        $newQuantity = $this->editingQuantities[$itemId] ?? $item->quantity_on_hand;

        $item->update(['quantity_on_hand' => (int) $newQuantity]);

        unset($this->editingQuantities[$itemId]);

        $this->dispatch('toast', type: 'success', message: 'Inventory updated.');
    }

    public function render()
    {
        $store = app('current_store');

        $items = InventoryItem::query()
            ->where('inventory_items.store_id', $store->id)
            ->join('product_variants', 'inventory_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('product_variants.sku', 'like', '%'.$this->search.'%')
                        ->orWhere('products.title', 'like', '%'.$this->search.'%');
                });
            })
            ->select(
                'inventory_items.*',
                'products.title as product_title',
                'product_variants.sku',
            )
            ->orderBy('products.title')
            ->paginate(20);

        return view('livewire.admin.inventory.index', [
            'items' => $items,
        ]);
    }
}
