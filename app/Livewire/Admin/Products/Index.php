<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    /** @var array<int, int|string> */
    public array $selectedIds = [];

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
        $this->clearSelection();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    /**
     * Toggle the sort column or flip the direction (spec 03 §3).
     */
    public function sortBy(string $field): void
    {
        if (! in_array($field, ['title', 'inventory', 'updated_at'], true)) {
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

    /**
     * Select or deselect every product visible on the current page.
     */
    public function toggleSelectAll(): void
    {
        $visibleIds = $this->productsQuery()->paginate($this->perPage())->pluck('id')->all();

        if ($this->allVisibleSelected($visibleIds)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $visibleIds));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $visibleIds)));
        }
    }

    /**
     * Archive every selected product the user may archive (spec 03 §3).
     */
    public function bulkArchive(ProductService $products): void
    {
        $archived = 0;

        foreach ($this->selectedProducts() as $product) {
            if (! Gate::allows('archive', $product) || $product->status === ProductStatus::Archived) {
                continue;
            }

            $products->transitionStatus($product, ProductStatus::Archived);
            $archived++;
        }

        $this->clearSelection();

        $archived > 0
            ? $this->dispatch('toast', type: 'success', message: trans_choice(':count product archived.|:count products archived.', $archived))
            : $this->dispatch('toast', type: 'error', message: 'No selected products could be archived.');
    }

    /**
     * Set every selected product the user may update to active.
     */
    public function bulkSetActive(ProductService $products): void
    {
        $activated = 0;
        $skipped = 0;

        foreach ($this->selectedProducts() as $product) {
            if (! Gate::allows('update', $product) || $product->status === ProductStatus::Active) {
                continue;
            }

            try {
                $products->transitionStatus($product, ProductStatus::Active);
                $activated++;
            } catch (InvalidProductTransitionException) {
                $skipped++;
            }
        }

        $this->clearSelection();

        if ($activated > 0) {
            $this->dispatch('toast', type: 'success', message: trans_choice(':count product activated.|:count products activated.', $activated));
        }

        if ($skipped > 0) {
            $this->dispatch('toast', type: 'error', message: trans_choice(':count product could not be activated.|:count products could not be activated.', $skipped));
        }

        if ($activated === 0 && $skipped === 0) {
            $this->dispatch('toast', type: 'error', message: 'No selected products could be activated.');
        }
    }

    /**
     * Open the bulk delete confirmation modal.
     */
    public function confirmBulkDelete(): void
    {
        $this->confirmingBulkDelete = true;
    }

    /**
     * Hard-delete selected drafts; products with orders or non-draft status
     * are refused by the service and reported (spec 03 §3 modal).
     */
    public function bulkDelete(ProductService $products): void
    {
        $this->confirmingBulkDelete = false;

        $deleted = 0;
        $skipped = 0;

        foreach ($this->selectedProducts() as $product) {
            if (! Gate::allows('delete', $product)) {
                $skipped++;

                continue;
            }

            try {
                $products->delete($product);
                $deleted++;
            } catch (InvalidProductTransitionException) {
                $skipped++;
            }
        }

        $this->clearSelection();

        if ($deleted > 0) {
            $this->dispatch('toast', type: 'success', message: trans_choice(':count product deleted.|:count products deleted.', $deleted));
        }

        if ($skipped > 0) {
            $this->dispatch('toast', type: 'error', message: trans_choice(':count product could not be deleted. Only drafts without orders can be deleted.|:count products could not be deleted. Only drafts without orders can be deleted.', $skipped));
        }
    }

    public function render(): View
    {
        $products = $this->productsQuery()
            ->with(['variants.inventoryItem', 'media'])
            ->withCount('variants')
            ->paginate($this->perPage());

        return view('livewire.admin.products.index', [
            'products' => $products,
            'productTypes' => $this->productTypes(),
            'hasProducts' => Product::query()->exists(),
        ])->layout('admin.layouts.app')->title('Products');
    }

    /**
     * Base query with search, filters, and sorting applied (spec 03 §3).
     *
     * @return Builder<Product>
     */
    private function productsQuery(): Builder
    {
        return Product::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('vendor', 'like', $term)
                        ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', $term));
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn (Builder $query) => $query->where('product_type', $this->typeFilter))
            ->when(
                $this->sortField === 'inventory',
                fn (Builder $query) => $query->orderBy($this->inventorySubquery(), $this->sortDirection),
                fn (Builder $query) => $query->orderBy($this->sortField, $this->sortDirection),
            );
    }

    /**
     * Sum of on-hand inventory across all variants of a product.
     */
    private function inventorySubquery(): Builder
    {
        return InventoryItem::query()
            ->selectRaw('COALESCE(SUM(inventory_items.quantity_on_hand), 0)')
            ->join('product_variants', 'inventory_items.variant_id', '=', 'product_variants.id')
            ->whereColumn('product_variants.product_id', 'products.id');
    }

    /**
     * Distinct product types for the type filter.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function productTypes(): \Illuminate\Support\Collection
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type');
    }

    /**
     * Selected products, re-queried so stale ids are ignored.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function selectedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()->whereIn('id', $this->selectedIds)->get();
    }

    /**
     * @param  list<int>  $visibleIds
     */
    private function allVisibleSelected(array $visibleIds): bool
    {
        return $visibleIds !== [] && array_diff($visibleIds, array_map('intval', $this->selectedIds)) === [];
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    private function perPage(): int
    {
        return 15;
    }
}
