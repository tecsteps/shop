<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
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

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedIds = [];
            $this->selectAll = false;
        } else {
            $this->selectedIds = $this->products->pluck('id')->toArray();
            $this->selectAll = true;
        }
    }

    public function bulkArchive(): void
    {
        Product::whereIn('id', $this->selectedIds)->update(['status' => ProductStatus::Archived]);
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: __('Products archived.'));
    }

    public function bulkSetActive(): void
    {
        Product::whereIn('id', $this->selectedIds)->update(['status' => ProductStatus::Active]);
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: __('Products set to active.'));
    }

    public function bulkDelete(): void
    {
        Product::whereIn('id', $this->selectedIds)->delete();
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: __('Products deleted.'));
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $store = app('current_store');

        return Product::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->withCount('variants')
            ->with(['media' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function productTypes(): array
    {
        $store = app('current_store');

        return Product::query()
            ->where('store_id', $store->id)
            ->whereNotNull('product_type')
            ->distinct()
            ->pluck('product_type')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin.products.index');
    }
}
