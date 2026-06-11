<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Exceptions\ProductDeletionException;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\ProductService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $typeFilter = 'all';

    /** @var list<int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->updatedStatusFilter();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['title', 'inventory_quantity', 'updated_at'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selectedIds = $value
            ? $this->products->getCollection()->pluck('id')->all()
            : [];
    }

    public function bulkSetActive(): void
    {
        $this->applyBulkTransition(ProductStatus::Active, __(':count product(s) set to active.'));
    }

    public function bulkArchive(): void
    {
        $this->applyBulkTransition(ProductStatus::Archived, __(':count product(s) archived.'));
    }

    /**
     * Bulk delete: drafts without order references are hard-deleted, anything
     * else falls back to archiving (spec 03 section 3 delete modal copy).
     */
    public function bulkDelete(): void
    {
        $products = $this->selectedProducts();
        $service = app(ProductService::class);

        foreach ($products as $product) {
            $this->authorize('delete', $product);
        }

        foreach ($products as $product) {
            try {
                $service->delete($product);
            } catch (ProductDeletionException) {
                try {
                    $service->transitionStatus($product, ProductStatus::Archived);
                } catch (InvalidProductTransitionException) {
                    // Already archived or otherwise untransitionable; skip.
                }
            }
        }

        Flux::modal('confirm-bulk-delete')->close();

        $this->toast(__(':count product(s) deleted or archived.', ['count' => $products->count()]));
        $this->clearSelection();
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['media' => fn ($query) => $query->limit(1)])
            ->withCount('variants')
            ->addSelect([
                'inventory_quantity' => InventoryItem::query()
                    ->withoutGlobalScopes()
                    ->join('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->selectRaw('coalesce(sum(inventory_items.quantity_on_hand), 0)'),
            ])
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->where('product_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    /**
     * Distinct product types for the type filter dropdown.
     *
     * @return list<string>
     */
    #[Computed]
    public function productTypes(): array
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type')
            ->all();
    }

    #[Computed]
    public function hasAnyProducts(): bool
    {
        return Product::query()->exists();
    }

    public function render(): View
    {
        return view('livewire.admin.products.index')->title(__('Products'));
    }

    protected function applyBulkTransition(ProductStatus $status, string $message): void
    {
        $products = $this->selectedProducts();
        $service = app(ProductService::class);
        $count = 0;

        foreach ($products as $product) {
            $this->authorize($status === ProductStatus::Archived ? 'archive' : 'update', $product);
        }

        foreach ($products as $product) {
            try {
                $service->transitionStatus($product, $status);
                $count++;
            } catch (InvalidProductTransitionException $exception) {
                $this->toast($exception->getMessage(), 'error');
            }
        }

        if ($count > 0) {
            $this->toast(str_replace(':count', (string) $count, $message));
        }

        $this->clearSelection();
    }

    /**
     * @return Collection<int, Product>
     */
    protected function selectedProducts(): Collection
    {
        return Product::query()->whereIn('id', $this->selectedIds)->get();
    }

    protected function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }
}
