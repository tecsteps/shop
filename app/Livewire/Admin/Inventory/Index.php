<?php

namespace App\Livewire\Admin\Inventory;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stock = 'all';

    /** @var array<int, int> */
    public array $quantities = [];

    /** @var array<int, string> */
    public array $policies = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStock(): void
    {
        $this->resetPage();
    }

    public function save(int $inventoryId): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
        $item = InventoryItem::query()->findOrFail($inventoryId);
        $data = $this->validate([
            'quantities.'.$inventoryId => ['required', 'integer', 'min:0'],
            'policies.'.$inventoryId => ['required', 'in:deny,continue'],
        ]);
        $item->update(['quantity_on_hand' => $data['quantities'][$inventoryId], 'policy' => $data['policies'][$inventoryId]]);
        $this->dispatch('toast', message: 'Inventory updated.');
    }

    public function render(): View
    {
        $items = InventoryItem::query()
            ->with('variant.product')
            ->when($this->search !== '', fn ($query) => $query->whereHas('variant.product', fn ($product) => $product->where('title', 'like', '%'.$this->search.'%'))->orWhereHas('variant', fn ($variant) => $variant->where('sku', 'like', '%'.$this->search.'%')))
            ->when($this->stock === 'out', fn ($query) => $query->whereColumn('quantity_on_hand', '<=', 'quantity_reserved'))
            ->when($this->stock === 'low', fn ($query) => $query->whereRaw('(quantity_on_hand - quantity_reserved) between 1 and 10'))
            ->latest('updated_at')
            ->paginate(25);

        foreach ($items as $item) {
            $this->quantities[$item->id] ??= $item->quantity_on_hand;
            $this->policies[$item->id] ??= $item->policy instanceof InventoryPolicy ? $item->policy->value : (string) $item->policy;
        }

        return view('livewire.admin.inventory.index', compact('items'))->layout('layouts.admin');
    }
}
