<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use App\Services\Inventory\InventoryService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public array $adjustments = [];

    public function adjust(int $itemId, InventoryService $service): void
    {
        $item = InventoryItem::find($itemId);
        if (! $item) {
            return;
        }

        $new = (int) ($this->adjustments[$itemId] ?? $item->quantity_on_hand);
        $service->adjustOnHand($item, $new);
        session()->flash('success', 'Inventory updated.');
    }

    public function render()
    {
        $items = InventoryItem::query()->with('variant.product')->get();

        return view('livewire.admin.inventory.index', compact('items'))->title('Inventory');
    }
}
