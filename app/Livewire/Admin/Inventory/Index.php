<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

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

    public function items(): LengthAwarePaginator
    {
        return InventoryItem::query()
            ->with(['variant.product'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->whereHas('variant', function (Builder $query) use ($search): void {
                    $query
                        ->where('sku', 'like', $search)
                        ->orWhereHas('product', fn (Builder $query) => $query->where('title', 'like', $search));
                });
            })
            ->when($this->stockFilter === 'low', fn (Builder $query) => $query->whereRaw('(quantity_on_hand - quantity_reserved) BETWEEN 1 AND 10'))
            ->when($this->stockFilter === 'out', fn (Builder $query) => $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0'))
            ->orderBy('quantity_on_hand')
            ->paginate(20);
    }

    public function render(): mixed
    {
        return view('livewire.admin.inventory.index', [
            'items' => $this->items(),
        ])->layout('layouts.app', [
            'title' => __('Inventory'),
        ]);
    }
}
