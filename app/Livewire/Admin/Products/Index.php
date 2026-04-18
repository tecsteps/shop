<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function bulkArchive(): void
    {
        Product::query()
            ->whereIn('id', $this->selected)
            ->update(['status' => ProductStatus::Archived->value]);
        $this->selected = [];
    }

    public function bulkDelete(ProductService $service): void
    {
        foreach (Product::query()->whereIn('id', $this->selected)->get() as $product) {
            try {
                $service->delete($product);
            } catch (\Throwable $e) {
                // skip products that cannot be deleted
            }
        }
        $this->selected = [];
    }

    public function render()
    {
        $query = Product::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('id');

        return view('livewire.admin.products.index', [
            'products' => $query->paginate(20),
            'statuses' => ProductStatus::cases(),
        ]);
    }
}
