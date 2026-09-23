<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    private const LOW_STOCK_THRESHOLD = 5;

    public string $search = '';

    public string $stockFilter = 'all';

    /** @var array<int, int|string> */
    public array $quantities = [];

    /** @var array<int, string> */
    public array $policies = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        if (! in_array($this->stockFilter, ['all', 'in_stock', 'low_stock', 'out_of_stock'], true)) {
            $this->stockFilter = 'all';
        }

        $this->resetPage();
    }

    public function saveInventoryItem(int $inventoryItemId): void
    {
        $store = $this->currentStore();
        $inventoryItem = InventoryItem::query()
            ->where('store_id', $store->id)
            ->whereHas('variant.product')
            ->with(['variant.product'])
            ->findOrFail($inventoryItemId);

        Gate::authorize('update', $inventoryItem->variant->product);

        $this->quantities[$inventoryItemId] ??= $inventoryItem->quantity_on_hand;
        $this->policies[$inventoryItemId] ??= $inventoryItem->policy;

        $validated = $this->validate([
            "quantities.{$inventoryItemId}" => ['required', 'integer', 'min:0'],
            "policies.{$inventoryItemId}" => ['required', Rule::in(['deny', 'continue'])],
        ]);

        $inventoryItem->update([
            'quantity_on_hand' => (int) $validated['quantities'][$inventoryItemId],
            'policy' => $validated['policies'][$inventoryItemId],
        ]);

        session()->flash('status', 'Inventory updated.');
    }

    #[Computed]
    public function inventoryItems(): LengthAwarePaginator
    {
        return $this->inventoryItemsQuery()->paginate(20);
    }

    public function render(): mixed
    {
        Gate::authorize('viewAny', Product::class);

        $inventoryItems = $this->inventoryItems;

        foreach ($inventoryItems as $inventoryItem) {
            $this->quantities[$inventoryItem->id] ??= $inventoryItem->quantity_on_hand;
            $this->policies[$inventoryItem->id] ??= $inventoryItem->policy;
        }

        return view('livewire.admin.inventory.index', [
            'inventoryItems' => $inventoryItems,
            'canManageInventory' => Gate::allows('manage-products'),
        ])
            ->layout('layouts.admin');
    }

    private function inventoryItemsQuery(): Builder
    {
        $store = $this->currentStore();
        $query = InventoryItem::query()
            ->where('store_id', $store->id)
            ->whereHas('variant.product')
            ->orderBy('id')
            ->with(['variant.product', 'variant.optionValues.option']);

        $searchTerm = trim($this->search);

        if ($searchTerm !== '') {
            $pattern = '%'.addcslashes($searchTerm, '%_\\').'%';

            $query->whereHas('variant', function (Builder $variantQuery) use ($pattern): void {
                $variantQuery
                    ->where('sku', 'like', $pattern)
                    ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('title', 'like', $pattern));
            });
        }

        return match ($this->stockFilter) {
            'in_stock' => $query->whereColumn('quantity_on_hand', '>', 'quantity_reserved'),
            'low_stock' => $query->whereRaw('quantity_on_hand - quantity_reserved between ? and ?', [1, self::LOW_STOCK_THRESHOLD]),
            'out_of_stock' => $query->whereColumn('quantity_on_hand', '<=', 'quantity_reserved'),
            default => $query,
        };
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
