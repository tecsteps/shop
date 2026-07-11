<?php

namespace App\Livewire\Admin\Inventory;

use App\Enums\StoreUserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $stockFilter = 'all';

    public function mount(): void
    {
        $this->authorizeStore();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $this->authorizeStore([StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
        abort_if($quantity < 0, 422);
        InventoryItem::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($itemId)->update(['quantity_on_hand' => $quantity]);
        $this->toast('Inventory updated.');
    }

    #[Computed]
    public function inventoryItems()
    {
        return InventoryItem::query()->where('store_id', $this->currentStore()->getKey())
            ->with(['variant.product', 'variant.optionValues'])
            ->when($this->search, fn (Builder $query) => $query->whereHas('variant', fn (Builder $variant) => $variant->where('sku', 'like', '%'.$this->search.'%')->orWhereHas('product', fn (Builder $product) => $product->where('title', 'like', '%'.$this->search.'%'))))
            ->when($this->stockFilter === 'in_stock', fn (Builder $query) => $query->where('quantity_on_hand', '>', 5))
            ->when($this->stockFilter === 'low_stock', fn (Builder $query) => $query->whereBetween('quantity_on_hand', [1, 5]))
            ->when($this->stockFilter === 'out_of_stock', fn (Builder $query) => $query->where('quantity_on_hand', '<=', 0))
            ->orderBy('quantity_on_hand')->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.inventory.index');
    }
}
