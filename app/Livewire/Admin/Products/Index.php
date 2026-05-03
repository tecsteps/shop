<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active';

    public string $typeFilter = 'all';

    /**
     * @var array<int, int>
     */
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
        if (! in_array($field, ['title', 'updated_at'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        $this->selectedIds = $this->selectAll
            ? $this->products()->pluck('id')->map(fn (int $id): int => $id)->all()
            : [];
    }

    public function bulkArchive(): void
    {
        $this->transitionSelected(ProductStatus::Archived);
    }

    public function bulkSetActive(): void
    {
        $this->transitionSelected(ProductStatus::Active);
    }

    public function bulkDelete(): void
    {
        $this->transitionSelected(ProductStatus::Archived);
    }

    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['variants.inventoryItem'])
            ->withCount('variants')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', $search)
                        ->orWhere('vendor', 'like', $search)
                        ->orWhere('product_type', 'like', $search)
                        ->orWhereHas('variants', fn (Builder $query) => $query->where('sku', 'like', $search));
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn (Builder $query) => $query->where('product_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function productTypes(): Collection
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type');
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.index', [
            'products' => $this->products(),
            'productTypes' => $this->productTypes(),
        ])->layout('layouts.app', [
            'title' => __('Products'),
        ]);
    }

    private function transitionSelected(ProductStatus $status): void
    {
        $service = app(ProductService::class);

        Product::query()
            ->whereKey($this->selectedIds)
            ->get()
            ->each(fn (Product $product) => $service->transitionStatus($product, $status));

        $this->selectedIds = [];
        $this->selectAll = false;

        $this->dispatch('toast', type: 'success', message: __('Products updated'));
    }
}
