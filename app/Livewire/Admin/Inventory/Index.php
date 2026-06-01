<?php

namespace App\Livewire\Admin\Inventory;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Inventory list: searchable by product title or SKU, filterable by stock
 * level, with inline-editable on-hand quantities.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

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

    public function updateOnHand(int $itemId, int $quantity): void
    {
        $this->authorize('viewAny', Product::class);

        $item = InventoryItem::query()->find($itemId);

        if ($item !== null) {
            $item->update(['quantity_on_hand' => max(0, $quantity)]);
            $this->dispatch('toast', type: 'success', message: __('Inventory updated'));
        }
    }

    public function getInventoryItemsProperty()
    {
        return InventoryItem::query()
            ->with(['variant.product', 'variant.optionValues'])
            ->whereHas('variant.product')
            ->when($this->search !== '', fn (Builder $q) => $q->where(function (Builder $q): void {
                $q->whereHas('variant', fn (Builder $v) => $v->where('sku', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('variant.product', fn (Builder $p) => $p->where('title', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->stockFilter === 'in_stock', fn (Builder $q) => $q->where('quantity_on_hand', '>', 10))
            ->when($this->stockFilter === 'low_stock', fn (Builder $q) => $q->whereBetween('quantity_on_hand', [1, 10]))
            ->when($this->stockFilter === 'out_of_stock', fn (Builder $q) => $q->where('quantity_on_hand', '<=', 0))
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.inventory.index');
    }
}
