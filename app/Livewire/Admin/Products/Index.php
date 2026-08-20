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

    public string $message = '';

    public function archive(int $productId, ProductService $products): void
    {
        $product = Product::query()->findOrFail($productId);
        $this->authorize('archive', $product);
        $products->transitionStatus($product, ProductStatus::Archived);
        $this->message = 'Product archived';
    }

    public function render(): mixed
    {
        $products = Product::query()->with(['variants.inventory'])->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))->latest()->paginate(15);

        return view('livewire.admin.products.index', compact('products'))->layout('layouts.admin');
    }
}
