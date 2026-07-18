<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Products')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public array $selectedIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        Product::query()->whereKey($this->selectedIds)->get()->each(function (Product $product) use ($status): void {
            Gate::authorize('update', $product);
            $product->update(['status' => $status]);
        });
        $this->selectedIds = [];
        $this->dispatch('toast', type: 'success', message: 'Products updated.');
    }

    public function products(): LengthAwarePaginator
    {
        return Product::query()->with(['variants.inventoryItem'])->withCount('variants')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$this->search.'%')->orWhere('vendor', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('updated_at')->paginate(20);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Product::class);

        return view('livewire.admin.products.index', ['products' => $this->products()]);
    }
}
