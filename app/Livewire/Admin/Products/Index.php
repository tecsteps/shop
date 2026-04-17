<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    /** @var array<int> */
    public array $selected = [];

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

    public function archiveSelected(): void
    {
        $this->authorize('update', Product::class);

        $productService = app(ProductService::class);
        $products = Product::query()->whereIn('id', $this->selected)->get();

        foreach ($products as $product) {
            try {
                $productService->transitionStatus($product, ProductStatus::Archived);
            } catch (\Exception) {
                // Skip products that cannot be archived
            }
        }

        $this->selected = [];
        session()->flash('toast', ['type' => 'success', 'message' => __('Products archived.')]);
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteSelected(): void
    {
        $this->authorize('delete', Product::class);

        $productService = app(ProductService::class);
        $products = Product::query()->whereIn('id', $this->selected)->get();

        foreach ($products as $product) {
            try {
                $productService->delete($product);
            } catch (\Exception) {
                // Skip products that cannot be deleted
            }
        }

        $this->selected = [];
        $this->showDeleteModal = false;
        session()->flash('toast', ['type' => 'success', 'message' => __('Products deleted.')]);
    }

    public function render(): View
    {
        $query = Product::query()->with(['variants.inventoryItem']);

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.products.index', [
            'products' => $query->latest()->paginate(15),
        ]);
    }
}
