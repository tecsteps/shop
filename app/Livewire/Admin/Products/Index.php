<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = '';

    /** @var list<int|string> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorizeAction('viewAny', Product::class);
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
        abort_unless(in_array($field, ['title', 'updated_at', 'product_type', 'vendor'], true), 400);
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
        $this->selectedIds = $this->selectAll ? $this->products->getCollection()->modelKeys() : [];
    }

    public function bulkSetActive(): void
    {
        $this->transitionSelected(ProductStatus::Active, 'Products activated.');
    }

    public function bulkArchive(): void
    {
        $this->transitionSelected(ProductStatus::Archived, 'Products archived.');
    }

    public function bulkDelete(): void
    {
        $products = $this->selectedProducts();
        foreach ($products as $product) {
            $this->authorizeAction('delete', $product);
            try {
                app(ProductService::class)->delete($product);
            } catch (\Throwable) {
                app(ProductService::class)->transitionStatus($product, ProductStatus::Archived);
            }
        }
        $this->clearSelection();
        $this->toast('Selected products were removed or archived.');
    }

    private function transitionSelected(ProductStatus $status, string $message): void
    {
        try {
            foreach ($this->selectedProducts() as $product) {
                $this->authorizeAction('update', $product);
                app(ProductService::class)->transitionStatus($product, $status);
            }
            $this->toast($message);
            $this->clearSelection();
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    private function selectedProducts(): iterable
    {
        return Product::query()->whereKey(array_map('intval', $this->selectedIds))->get();
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['variants.inventoryItem', 'media' => fn ($query) => $query->orderBy('position')->limit(1)])
            ->withCount('variants')
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('title', 'like', '%'.$this->search.'%')->orWhere('vendor', 'like', '%'.$this->search.'%')->orWhere('handle', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn (Builder $query) => $query->where('product_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)->paginate(20);
    }

    #[Computed]
    public function productTypes(): array
    {
        return Product::query()->whereNotNull('product_type')->where('product_type', '!=', '')->distinct()->orderBy('product_type')->pluck('product_type')->all();
    }

    public function render(): View
    {
        return $this->admin(view('admin.products.index'), 'Products', [['label' => 'Products']]);
    }
}
