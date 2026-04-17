<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    /** @var array<int> */
    public array $selectedIds = [];

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

    public function bulkSetActive(): void
    {
        Product::whereIn('id', $this->selectedIds)->update(['status' => 'active']);
        $this->selectedIds = [];
        $this->dispatch('toast', type: 'success', message: 'Products updated.');
    }

    public function bulkArchive(): void
    {
        Product::whereIn('id', $this->selectedIds)->update(['status' => 'archived']);
        $this->selectedIds = [];
        $this->dispatch('toast', type: 'success', message: 'Products archived.');
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::findOrFail($id);

        if ($product->status !== ProductStatus::Draft) {
            $this->dispatch('toast', type: 'error', message: 'Only draft products can be deleted.');

            return;
        }

        $product->delete();
        $this->dispatch('toast', type: 'success', message: 'Product deleted.');
    }

    public function render(): mixed
    {
        $query = Product::query()
            ->withCount('variants')
            ->with('media')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.admin.products.index', [
            'products' => $query->paginate(20),
        ])->layout('layouts.admin.app', [
            'title' => 'Products',
        ]);
    }
}
