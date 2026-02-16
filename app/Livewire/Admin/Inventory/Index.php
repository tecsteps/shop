<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Inventory'])]
class Index extends Component
{
    use WithPagination;

    public function adjustQuantity(int $itemId, int $adjustment): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);
        $item->update(['quantity_on_hand' => $item->quantity_on_hand + $adjustment]);
    }

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.inventory.index', [
            'items' => InventoryItem::query()
                ->where('store_id', $store->id)
                ->with('variant.product')
                ->paginate(20),
        ]);
    }
}
