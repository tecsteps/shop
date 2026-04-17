<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
        $item = InventoryItem::find($itemId);

        if ($item) {
            $item->update(['quantity_on_hand' => max(0, $quantity)]);
            $this->dispatch('toast', type: 'success', message: 'Inventory updated.');
        }
    }

    #[Computed]
    public function inventoryItems(): LengthAwarePaginator
    {
        $query = InventoryItem::query()
            ->with(['variant.product']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sku', 'like', '%'.$this->search.'%')
                    ->orWhereHas('variant', function ($vq) {
                        $vq->where('title', 'like', '%'.$this->search.'%')
                            ->orWhereHas('product', function ($pq) {
                                $pq->where('title', 'like', '%'.$this->search.'%');
                            });
                    });
            });
        }

        if ($this->stockFilter === 'low_stock') {
            $query->whereRaw('(quantity_on_hand - quantity_reserved) < 5')
                ->whereRaw('(quantity_on_hand - quantity_reserved) > 0');
        } elseif ($this->stockFilter === 'out_of_stock') {
            $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0');
        } elseif ($this->stockFilter === 'in_stock') {
            $query->whereRaw('(quantity_on_hand - quantity_reserved) > 0');
        }

        return $query->orderBy('id')->paginate(30);
    }

    public function render()
    {
        return view('livewire.admin.inventory.index')
            ->layout('layouts.admin', ['title' => 'Inventory']);
    }
}
