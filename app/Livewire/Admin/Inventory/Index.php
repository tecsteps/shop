<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Inventory')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render(): View
    {
        $items = InventoryItem::query()->with('variant.product')
            ->when($this->search, fn ($query) => $query->whereHas('variant', fn ($query) => $query->where('sku', 'like', '%'.$this->search.'%')->orWhereHas('product', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))))
            ->paginate(20);

        return view('livewire.admin.inventory.index', compact('items'));
    }
}
