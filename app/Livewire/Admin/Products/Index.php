<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Products'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    /** @var array<int> */
    public array $selected = [];

    public bool $selectAll = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selected = $this->getProductsQuery()->pluck('id')->all();
        } else {
            $this->selected = [];
        }
    }

    public function bulkArchive(): void
    {
        Product::query()->whereIn('id', $this->selected)->update(['status' => ProductStatus::Archived]);
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Products archived.');
    }

    public function bulkDelete(): void
    {
        Product::query()->whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Products deleted.');
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.index', [
            'products' => $this->getProductsQuery()->paginate(15),
        ]);
    }

    private function getProductsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $store = app('current_store');

        return Product::query()
            ->where('store_id', $store->id)
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'variants.inventoryItem', 'media'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest();
    }
}
