<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use UsesAdminStore;
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    /**
     * @var array<int, int>
     */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function archive(int $productId, ProductService $products): void
    {
        $product = Product::query()->whereKey($productId)->firstOrFail();
        Gate::authorize('archive', $product);

        $products->transitionStatus($product, ProductStatus::Archived);
        $this->notify('Product archived.');
    }

    public function delete(int $productId, ProductService $products): void
    {
        $product = Product::query()->whereKey($productId)->firstOrFail();
        Gate::authorize('delete', $product);

        $products->delete($product);
        $this->notify('Product deleted.');
    }

    public function bulkArchive(ProductService $products): void
    {
        Product::query()
            ->whereIn('id', $this->selected)
            ->get()
            ->each(function (Product $product) use ($products): void {
                Gate::authorize('archive', $product);
                $products->transitionStatus($product, ProductStatus::Archived);
            });

        $this->selected = [];
        $this->notify('Selected products archived.');
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Product::class);

        return view('livewire.admin.products.index', [
            'products' => Product::query()
                ->with('variants.inventoryItem')
                ->withCount('variants')
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('handle', 'like', '%'.$this->search.'%')
                        ->orWhere('vendor', 'like', '%'.$this->search.'%');
                }))
                ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
                ->latest('updated_at')
                ->orderBy('title')
                ->paginate(10),
            'statuses' => ProductStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Products',
        ]);
    }
}
