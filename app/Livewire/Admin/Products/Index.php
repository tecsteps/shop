<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    /** @var list<int> */
    public array $selectedIds = [];

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        abort_unless(in_array($field, ['title', 'updated_at'], true), 422);
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    public function bulkSetActive(ProductService $service): void
    {
        $this->selectedProducts()->each(function (Product $product) use ($service): void {
            Gate::authorize('update', $product);
            $service->transitionStatus($product, ProductStatus::Active);
        });
        $this->selectedIds = [];
        $this->toast('Products activated.');
    }

    public function bulkArchive(ProductService $service): void
    {
        $this->selectedProducts()->each(function (Product $product) use ($service): void {
            Gate::authorize('archive', $product);
            $service->transitionStatus($product, ProductStatus::Archived);
        });
        $this->selectedIds = [];
        $this->toast('Products archived.');
    }

    #[Computed]
    public function products()
    {
        return Product::query()->where('store_id', $this->currentStore()->getKey())
            ->with(['variants.inventoryItem', 'media' => fn ($query) => $query->limit(1)])
            ->withCount('variants')
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('title', 'like', '%'.$this->search.'%')->orWhere('vendor', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn (Builder $query) => $query->where('product_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    #[Computed]
    public function productTypes()
    {
        return Product::query()->where('store_id', $this->currentStore()->getKey())->whereNotNull('product_type')->distinct()->orderBy('product_type')->pluck('product_type');
    }

    private function selectedProducts()
    {
        return Product::query()->where('store_id', $this->currentStore()->getKey())->whereKey($this->selectedIds)->get();
    }

    public function render()
    {
        return view('livewire.admin.products.index');
    }
}
