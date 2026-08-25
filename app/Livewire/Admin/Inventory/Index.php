<?php

namespace App\Livewire\Admin\Inventory;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use DispatchesToasts, WithPagination;

    #[Layout('layouts.admin.app')]
    public string $search = '';

    public string $stockFilter = 'all';

    public ?int $editingId = null;

    public ?int $editingQuantity = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->editingId = null;
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
        $this->editingId = null;
    }

    #[Computed]
    public function inventoryItems(): LengthAwarePaginator
    {
        $query = InventoryItem::query()
            ->with(['variant.product', 'variant.optionValues'])
            ->orderBy('id');

        if (trim($this->search) !== '') {
            $query->whereHas('variant', function ($q) {
                $q->where('sku', 'like', '%'.trim($this->search).'%')
                    ->orWhereHas('product', fn ($p) => $p->where('title', 'like', '%'.trim($this->search).'%'));
            });
        }

        switch ($this->stockFilter) {
            case 'in_stock':
                $query->where('quantity_on_hand', '>', 0);
                break;
            case 'low_stock':
                $query->whereBetween('quantity_on_hand', [1, 5]);
                break;
            case 'out_of_stock':
                $query->where('quantity_on_hand', 0);
                break;
        }

        return $query->paginate(15);
    }

    public function startEdit(int $id, int $quantity): void
    {
        $this->editingId = $id;
        $this->editingQuantity = $quantity;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingQuantity = null;
    }

    public function saveQuantity(): void
    {
        if ($this->editingId === null || $this->editingQuantity === null) {
            return;
        }

        $item = InventoryItem::with('variant.product')->find($this->editingId);

        if (! $item) {
            $this->cancelEdit();

            return;
        }

        $this->authorize('update', $item->variant->product);

        $this->validate([
            'editingQuantity' => ['required', 'integer', 'min:0'],
        ]);

        $item->update(['quantity_on_hand' => $this->editingQuantity]);

        $this->toast('Inventory updated');

        $this->cancelEdit();
    }

    public function render()
    {
        return view('livewire.admin.inventory.index');
    }
}
