<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public string $status = 'active';

    public string $productType = 'all';

    public string $vendor = '';

    /** @var array<int, int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public string $message = '';

    public function updatedSelectAll(bool $selected): void
    {
        $this->selectedIds = $selected ? $this->filteredProductsQuery()->pluck('id')->all() : [];
    }

    public function archive(int $productId, ProductService $products): void
    {
        $product = Product::query()->findOrFail($productId);
        $this->authorize('archive', $product);
        $products->transitionStatus($product, ProductStatus::Archived);
        $this->message = 'Product archived';
    }

    public function bulkArchive(ProductService $products): void
    {
        foreach ($this->selectedIds as $productId) {
            $product = Product::query()->find($productId);

            if ($product !== null) {
                $this->authorize('archive', $product);
                $products->transitionStatus($product, ProductStatus::Archived);
            }
        }

        $this->reset(['selectedIds', 'selectAll']);
        $this->message = 'Selected products archived.';
    }

    public function render(): mixed
    {
        $products = $this->filteredProductsQuery()->with(['variants.inventory'])->latest()->paginate(15);

        return view('livewire.admin.products.index', compact('products'))->layout('layouts.admin');
    }

    private function filteredProductsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()->when($this->search !== '', fn ($query) => $query->where(function ($nested): void {
            $nested->where('title', 'like', '%'.$this->search.'%')->orWhere('handle', 'like', '%'.$this->search.'%');
        }))->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))->when($this->productType !== 'all', fn ($query) => $query->where('product_type', $this->productType))->when($this->vendor !== '', fn ($query) => $query->where('vendor', 'like', '%'.$this->vendor.'%'));
    }
}
