<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Products')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

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
            $this->selectedIds = $this->products->pluck('id')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function bulkArchive(): void
    {
        $this->authorize('viewAny', Product::class);
        $store = app('current_store');
        Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    public function bulkSetActive(): void
    {
        $this->authorize('viewAny', Product::class);
        $store = app('current_store');
        Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Active, 'published_at' => now()]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products set to active.');
    }

    public function bulkDelete(): void
    {
        $this->authorize('viewAny', Product::class);
        $store = app('current_store');
        Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products deleted.');
        $this->modal('confirm-bulk-delete')->close();
    }

    /**
     * @return LengthAwarePaginator<Product>
     */
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.products.index');
    }
}
