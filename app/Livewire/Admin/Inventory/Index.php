<?php

namespace App\Livewire\Admin\Inventory;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * @var array<int, int>
     */
    public array $quantities = [];

    /**
     * @var array<int, string>
     */
    public array $policies = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function saveItem(int $itemId): void
    {
        $this->validate([
            "quantities.$itemId" => ['required', 'integer', 'min:0'],
            "policies.$itemId" => ['required', Rule::in(array_map(fn (InventoryPolicy $policy): string => $policy->value, InventoryPolicy::cases()))],
        ]);

        InventoryItem::query()->whereKey($itemId)->firstOrFail()->forceFill([
            'quantity_on_hand' => $this->quantities[$itemId],
            'policy' => $this->policies[$itemId],
        ])->save();

        session()->flash('admin_toast', ['message' => 'Inventory updated.', 'type' => 'success']);
    }

    public function render(): View
    {
        $items = InventoryItem::query()
            ->with('variant.product')
            ->whereHas('variant.product', fn ($query) => $query->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%')))
            ->latest('variant_id')
            ->paginate(12);

        foreach ($items as $item) {
            $this->quantities[$item->id] ??= (int) $item->quantity_on_hand;
            $this->policies[$item->id] ??= $item->policy->value;
        }

        return view('livewire.admin.inventory.index', [
            'items' => $items,
            'policyOptions' => InventoryPolicy::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Inventory',
        ]);
    }
}
