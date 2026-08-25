<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use DispatchesToasts, WithPagination;

    #[Layout('layouts.admin.app')]
    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    /** @var list<int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public bool $confirmingBulkDelete = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function updatedSelectedIds(): void
    {
        $this->syncSelectAll();
    }

    public function updatedSelectAll(bool $value): void
    {
        $ids = collect($this->products->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->selectedIds = $value
            ? array_values(array_unique(array_merge($this->selectedIds, $ids)))
            : array_values(array_diff($this->selectedIds, $ids));
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $store = app('current_store');

        $query = Product::query()
            ->where('store_id', $store->id)
            ->with(['variants.inventoryItem', 'media' => fn ($q) => $q->orderBy('position')])
            ->withCount('variants');

        if (trim($this->search) !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.trim($this->search).'%')
                    ->orWhere('vendor', 'like', '%'.trim($this->search).'%')
                    ->orWhere('product_type', 'like', '%'.trim($this->search).'%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('product_type', $this->typeFilter);
        }

        if ($this->sortField === 'inventory') {
            $query->orderByDesc(DB::raw('(
                SELECT COALESCE(SUM(inventory_items.quantity_on_hand), 0)
                FROM inventory_items
                INNER JOIN product_variants ON product_variants.id = inventory_items.variant_id
                WHERE product_variants.product_id = products.id
            )'));
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query->paginate(15);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function productTypes(): array
    {
        return Product::query()
            ->where('store_id', app('current_store')->id)
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->pluck('product_type')
            ->all();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        $this->updatedSelectAll($this->selectAll);
    }

    public function bulkSetActive(): void
    {
        $this->applyBulk(fn (Product $product) => app(ProductService::class)->transitionStatus($product, ProductStatus::Active), 'product(s) set to active');
    }

    public function bulkArchive(): void
    {
        $this->applyBulk(fn (Product $product) => app(ProductService::class)->transitionStatus($product, ProductStatus::Archived), 'product(s) archived');
    }

    public function confirmBulkDelete(): void
    {
        $this->confirmingBulkDelete = true;
    }

    public function bulkDelete(): void
    {
        $this->confirmingBulkDelete = false;
        $this->applyBulk(fn (Product $product) => app(ProductService::class)->delete($product), 'product(s) deleted');
    }

    /**
     * @param  callable(Product): void  $action
     */
    private function applyBulk(callable $action, string $message): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $count = 0;

        foreach (Product::whereKey($this->selectedIds)->get() as $product) {
            if (! auth()->user()->can('update', $product)) {
                continue;
            }

            try {
                $action($product);
                $count++;
            } catch (\Throwable) {
                // Skip products that cannot be transitioned/deleted.
            }
        }

        $this->selectedIds = [];
        $this->selectAll = false;

        $this->toast("{$count} {$message}");
    }

    private function syncSelectAll(): void
    {
        $visibleIds = collect($this->products->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->selectAll = $visibleIds !== [] && array_diff($visibleIds, $this->selectedIds) === [];
    }

    public function render()
    {
        return view('livewire.admin.products.index');
    }
}
