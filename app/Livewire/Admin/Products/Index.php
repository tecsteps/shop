<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Product list: searchable, filterable, sortable, with bulk archive/activate/
 * delete actions and pagination. Reads are store-scoped via the global scope;
 * lifecycle transitions go through {@see ProductService} so the catalog state
 * machine and order-reference guards are enforced.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $typeFilter = 'all';

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    /** @var array<int, int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public bool $showDeleteModal = false;

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
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function toggleSelectAll(): void
    {
        $this->selectedIds = $this->selectAll
            ? $this->products->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : [];
    }

    public function bulkSetActive(): void
    {
        $this->transitionSelected(ProductStatus::Active);
    }

    public function bulkArchive(): void
    {
        $this->transitionSelected(ProductStatus::Archived);
    }

    public function confirmBulkDelete(): void
    {
        $this->showDeleteModal = true;
    }

    /**
     * Archive selected products (the spec's "delete" softens to archive when
     * orders reference a product). Drafts without order references are hard
     * deleted by the service; everything else is archived.
     */
    public function bulkDelete(ProductService $products): void
    {
        $count = 0;

        foreach ($this->selectedProducts() as $product) {
            $this->authorize('delete', $product);

            try {
                if ($product->status === ProductStatus::Draft) {
                    $products->delete($product);
                } else {
                    $products->transitionStatus($product, ProductStatus::Archived);
                }
                $count++;
            } catch (\Throwable) {
                $products->transitionStatus($product, ProductStatus::Archived);
                $count++;
            }
        }

        $this->showDeleteModal = false;
        $this->afterBulk($count, __(':count product(s) removed.', ['count' => $count]));
    }

    private function transitionSelected(ProductStatus $status): void
    {
        $service = app(ProductService::class);
        $count = 0;

        foreach ($this->selectedProducts() as $product) {
            $this->authorize($status === ProductStatus::Archived ? 'archive' : 'update', $product);

            try {
                $service->transitionStatus($product, $status);
                $count++;
            } catch (\Throwable) {
                // Skip products whose transition is blocked by the state machine.
            }
        }

        $this->afterBulk($count, __(':count product(s) updated.', ['count' => $count]));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function selectedProducts()
    {
        return Product::query()->whereIn('id', $this->selectedIds)->get();
    }

    private function afterBulk(int $count, string $message): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->resetPage();

        $this->dispatch('toast', type: $count > 0 ? 'success' : 'info', message: $message);
    }

    /**
     * Paginated, filtered, sorted products with the counts and relations the
     * table needs.
     */
    public function getProductsProperty()
    {
        return Product::query()
            ->withCount('variants')
            ->with(['media' => fn ($q) => $q->limit(1)])
            ->when($this->search !== '', fn (Builder $q) => $q->where(function (Builder $q): void {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('vendor', 'like', '%'.$this->search.'%')
                    ->orWhere('product_type', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn (Builder $q) => $q->where('product_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    /**
     * Distinct product types for the type filter.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getProductTypesProperty()
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type');
    }

    public function render()
    {
        return view('livewire.admin.products.index');
    }
}
