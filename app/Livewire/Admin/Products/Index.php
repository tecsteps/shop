<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function delete(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        Gate::authorize('delete', $product);
        app(\App\Services\ProductService::class)->delete($product);
        session()->flash('status', 'Product deleted.');
    }

    public function render(): mixed
    {
        Gate::authorize('viewAny', Product::class);
        $products = Product::query()->with(['variants.inventoryItem'])->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($this->search, '%_\\').'%'))
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.products.index', ['products' => $products])->layout('layouts.admin');
    }
}
