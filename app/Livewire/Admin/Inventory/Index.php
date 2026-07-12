<?php

namespace App\Livewire\Admin\Inventory;

use App\Livewire\Admin\AdminComponent;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $stockFilter = 'all';

    public function mount(): void
    {
        $this->authorizeAction('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function updateQuantity(int $itemId, mixed $quantity): void
    {
        $item = InventoryItem::query()->with('variant.product')->findOrFail($itemId);
        $this->authorizeAction('update', $item->variant->product);
        $validated = validator(['quantity' => $quantity], ['quantity' => ['required', 'integer', 'min:0', 'max:999999999']])->validate();
        $item->update(['quantity_on_hand' => $validated['quantity']]);
        $this->toast('Inventory updated.');
    }

    public function updatePolicy(int $itemId, string $policy): void
    {
        $item = InventoryItem::query()->with('variant.product')->findOrFail($itemId);
        $this->authorizeAction('update', $item->variant->product);
        abort_unless(in_array($policy, ['deny', 'continue'], true), 422);
        $item->update(['policy' => $policy]);
        $this->toast('Inventory policy updated.');
    }

    #[Computed]
    public function inventoryItems(): LengthAwarePaginator
    {
        return InventoryItem::query()->with(['variant.product', 'variant.optionValues.option'])
            ->when($this->search !== '', fn (Builder $query) => $query->whereHas('variant', fn (Builder $variant) => $variant->where('sku', 'like', '%'.$this->search.'%')->orWhereHas('product', fn (Builder $product) => $product->where('title', 'like', '%'.$this->search.'%'))))
            ->when($this->stockFilter === 'in_stock', fn (Builder $query) => $query->whereRaw('(quantity_on_hand - quantity_reserved) > 10'))
            ->when($this->stockFilter === 'low_stock', fn (Builder $query) => $query->whereRaw('(quantity_on_hand - quantity_reserved) between 1 and 10'))
            ->when($this->stockFilter === 'out_of_stock', fn (Builder $query) => $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0'))
            ->orderBy('id')->paginate(25);
    }

    public function render(): View
    {
        return $this->admin(view('admin.inventory.index'), 'Inventory', [['label' => 'Inventory']]);
    }
}
