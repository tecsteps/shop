<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $store = app('current_store');

        $query = ProductVariant::query()
            ->with(['product', 'inventoryItem'])
            ->whereHas('product', function ($q) use ($store) {
                $q->where('store_id', $store->id);
                if ($this->search !== '') {
                    $q->where('title', 'like', '%'.$this->search.'%');
                }
            });

        return view('livewire.admin.inventory.index', [
            'variants' => $query->paginate(25),
        ]);
    }
}
