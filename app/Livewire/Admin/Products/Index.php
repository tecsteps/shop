<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
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

    #[Url]
    public string $typeFilter = '';

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

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
        if ($this->selectAll) {
            $this->selectedIds = $this->products->pluck('id')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function bulkArchive(): void
    {
        Product::withoutGlobalScopes()
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived->value]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    public function bulkSetActive(): void
    {
        Product::withoutGlobalScopes()
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Active->value, 'published_at' => now()->toIso8601String()]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products set to active.');
    }

    public function bulkDelete(): void
    {
        Product::withoutGlobalScopes()
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived->value]);

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('toast', type: 'success', message: 'Products deleted.');
        $this->modal('confirm-bulk-delete')->close();
    }

    #[Computed]
    public function products(): mixed
    {
        $query = Product::query()
            ->withCount('variants')
            ->with(['media' => fn ($q) => $q->limit(1)]);

        if ($this->search !== '') {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== '') {
            $query->where('product_type', $this->typeFilter);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(15);
    }

    #[Computed]
    public function productTypes(): array
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->distinct()
            ->pluck('product_type')
            ->toArray();
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.index')
            ->layout('layouts.admin', [
                'breadcrumbs' => [
                    ['label' => 'Products'],
                ],
            ]);
    }
}
