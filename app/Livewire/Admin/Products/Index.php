<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesAdminStore, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    /** @var array<int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public bool $showBulkDeleteModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

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
            $this->selectedIds = $this->products()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        } else {
            $this->selectedIds = [];
        }
    }

    public function bulkSetActive(): void
    {
        $products = $this->selectedProducts();

        foreach ($products as $product) {
            Gate::authorize('update', $product);
        }

        foreach ($products as $product) {
            app(ProductService::class)->transitionStatus($product, ProductStatus::Active);
        }

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->toast('success', 'Products set to active.');
    }

    public function bulkArchive(): void
    {
        $products = $this->selectedProducts();

        foreach ($products as $product) {
            Gate::authorize('archive', $product);
        }

        foreach ($products as $product) {
            app(ProductService::class)->archive($product);
        }

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->toast('success', 'Products archived.');
    }

    public function confirmBulkDelete(): void
    {
        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete(): void
    {
        $products = $this->selectedProducts();

        foreach ($products as $product) {
            Gate::authorize('delete', $product);
        }

        $services = app(ProductService::class);

        foreach ($products as $product) {
            if ($product->status === ProductStatus::Draft) {
                $services->delete($product);
            } else {
                $services->archive($product);
            }
        }

        $this->showBulkDeleteModal = false;
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->toast('success', 'Products deleted.');
    }

    public function render()
    {
        return view('livewire.admin.products.index', [
            'products' => $this->products(),
            'productTypes' => Product::query()
                ->where('store_id', $this->store()->getKey())
                ->whereNotNull('product_type')
                ->distinct()
                ->orderBy('product_type')
                ->pluck('product_type'),
        ])->layout('livewire.admin.layout.app', ['title' => 'Products']);
    }

    protected function products()
    {
        $query = Product::query()
            ->where('store_id', $this->store()->getKey())
            ->with([
                'productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'productVariants.inventoryItem',
            ])
            ->withCount('productVariants');

        if ($this->search !== '') {
            $query->where(function ($inner): void {
                $inner->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('vendor', 'like', '%'.$this->search.'%')
                    ->orWhere('handle', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('product_type', $this->typeFilter);
        }

        $sortable = ['title', 'updated_at', 'created_at'];

        if (in_array($this->sortField, $sortable, true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderByDesc('updated_at');
        }

        return $query->paginate(25);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function selectedProducts()
    {
        return Product::query()
            ->where('store_id', $this->store()->getKey())
            ->whereIn('id', $this->selectedIds)
            ->get();
    }
}
