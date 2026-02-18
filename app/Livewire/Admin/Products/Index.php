<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    /** @var array<int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedIds = [];
            $this->selectAll = false;
        } else {
            /** @var Store $store */
            $store = app('current_store');
            /** @var array<int> $ids */
            $ids = Product::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->pluck('id')
                ->toArray();
            $this->selectedIds = $ids;
            $this->selectAll = true;
        }
    }

    public function bulkArchive(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        Product::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products archived successfully.');
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $query = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->withCount('variants');

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $products = $query->latest('updated_at')->paginate(15);

        return view('livewire.admin.products.index', [
            'products' => $products,
        ]);
    }
}
