<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
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
    public string $statusFilter = '';

    public int $perPage = 20;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function bulkArchive(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        Product::query()
            ->whereIn('id', $this->selectedIds)
            ->update(['status' => ProductStatus::Archived->value]);

        $this->selectedIds = [];
    }

    public function bulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        Product::query()
            ->whereIn('id', $this->selectedIds)
            ->where('status', ProductStatus::Draft->value)
            ->delete();

        $this->selectedIds = [];
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['variants' => fn ($q) => $q->where('is_default', true)])
            ->withCount('variants')
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.products.index', [
            'products' => $products,
        ]);
    }
}
