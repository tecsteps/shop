<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Products')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public bool $showDeleteModal = false;

    public ?int $deletingProductId = null;

    public string $deletingProductTitle = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $productId, string $title): void
    {
        $this->deletingProductId = $productId;
        $this->deletingProductTitle = $title;
        $this->showDeleteModal = true;
    }

    public function deleteProduct(ProductService $productService): void
    {
        if (! $this->deletingProductId) {
            return;
        }

        $store = app('current_store');
        $product = Product::query()
            ->where('store_id', $store->id)
            ->findOrFail($this->deletingProductId);

        try {
            $productService->delete($product);

            $this->dispatch('toast', type: 'success', message: 'Product deleted successfully.');
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->deletingProductId = null;
        $this->deletingProductTitle = '';
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingProductId = null;
        $this->deletingProductTitle = '';
    }

    public function render(): View
    {
        $store = app('current_store');

        $products = Product::query()
            ->where('store_id', $store->id)
            ->with(['variants.inventoryItem', 'media'])
            ->when($this->search, fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.products.index', [
            'products' => $products,
            'statuses' => ProductStatus::cases(),
        ]);
    }
}
