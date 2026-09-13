<?php

namespace App\Livewire\Admin\Inventory;

use App\Enums\InventoryPolicy;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;
use App\Traits\ChecksStoreRole;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ChecksStoreRole, ResolvesAdminStore, WithPagination;

    public string $search = '';

    public string $stockFilter = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

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
        $item = InventoryItem::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($itemId)
            ->firstOrFail();

        Gate::authorize('viewAny', Product::class);

        $this->authorizeMutation();

        app(InventoryService::class)->adjust($item->refresh(), max(0, $quantity));

        $this->toast('success', 'Inventory updated.');
    }

    public function updatePolicy(int $itemId, string $policy): void
    {
        $item = InventoryItem::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($itemId)
            ->firstOrFail();

        $this->authorizeMutation();

        app(InventoryService::class)->adjust(
            $item->refresh(),
            null,
            InventoryPolicy::from($policy)
        );

        $this->toast('success', 'Inventory policy updated.');
    }

    public function render()
    {
        $query = InventoryItem::query()
            ->where('inventory_items.store_id', $this->store()->getKey())
            ->with(['variant.product', 'variant.optionValues'])
            ->join('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select('inventory_items.*')
            ->orderBy('products.title')
            ->orderBy('product_variants.position');

        if ($this->search !== '') {
            $query->where(function ($inner): void {
                $inner->where('products.title', 'like', '%'.$this->search.'%')
                    ->orWhere('product_variants.sku', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->stockFilter === 'in_stock') {
            $query->where('inventory_items.quantity_on_hand', '>', 5);
        } elseif ($this->stockFilter === 'low_stock') {
            $query->whereBetween('inventory_items.quantity_on_hand', [1, 5]);
        } elseif ($this->stockFilter === 'out_of_stock') {
            $query->where('inventory_items.quantity_on_hand', '<=', 0);
        }

        return view('livewire.admin.inventory.index', [
            'inventoryItems' => $query->paginate(25),
        ])->layout('livewire.admin.layout.app', ['title' => 'Inventory']);
    }

    protected function authorizeMutation(): void
    {
        $user = auth()->user();

        abort_if($user === null, 403);
        abort_if(! $this->isOwnerAdminOrStaff($user, $this->store()->getKey()), 403);
    }
}
