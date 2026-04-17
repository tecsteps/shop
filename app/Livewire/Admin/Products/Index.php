<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

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

    public function updatedTypeFilter(): void
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
        $this->selectAll = ! $this->selectAll;
        $this->selectedIds = $this->selectAll
            ? $this->getProductsProperty()->pluck('id')->toArray()
            : [];
    }

    public function bulkSetActive(): void
    {
        Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Active]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products set to active.');
    }

    public function bulkArchive(): void
    {
        Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    public function confirmBulkDelete(): void
    {
        $this->modal('confirm-bulk-delete')->show();
    }

    public function bulkDelete(): void
    {
        Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->modal('confirm-bulk-delete')->close();
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    public function getProductsProperty()
    {
        $query = Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->withCount('variants')
            ->with(['media' => fn ($q) => $q->limit(1)]);

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('product_type', $this->typeFilter);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(20);
    }

    public function getProductTypesProperty(): array
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->whereNotNull('product_type')
            ->distinct()
            ->pluck('product_type')
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.products.index', [
            'products' => $this->products,
        ]);
    }
}
