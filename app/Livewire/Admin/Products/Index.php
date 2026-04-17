<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    /** @var array<int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public bool $showDeleteModal = false;

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
        $allowedFields = ['title', 'updated_at', 'created_at'];
        if (! in_array($field, $allowedFields)) {
            return;
        }

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
            $this->selectedIds = $this->products->pluck('id')->all();
        } else {
            $this->selectedIds = [];
        }
    }

    public function bulkSetActive(): void
    {
        $this->authorize('update', new Product);

        Product::whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Active, 'published_at' => now()]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products set to active.');
    }

    public function bulkArchive(): void
    {
        $this->authorize('update', new Product);

        Product::whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    public function confirmBulkDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function bulkDelete(): void
    {
        $this->authorize('delete', new Product);

        Product::whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->showDeleteModal = false;
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->with([
                'media' => fn ($q) => $q->orderBy('position')->limit(1),
                'variants.inventoryItem',
            ])
            ->withCount('variants');

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(20);
    }

    #[Computed]
    public function productTypes(): array
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->distinct()
            ->pluck('product_type')
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.products.index')
            ->layout('layouts.admin', ['title' => 'Products']);
    }
}
