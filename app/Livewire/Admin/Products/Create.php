<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Create extends Component
{
    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $vendor = '';

    public string $productType = '';

    public string $status = 'draft';

    public string $tags = '';

    public int $priceAmount = 0;

    public int $quantityOnHand = 0;

    public function mount(): void
    {
        $this->authorize('create', Product::class);
    }

    public function save(ProductService $service): mixed
    {
        $this->authorize('create', Product::class);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', ProductStatus::values())],
            'tags' => ['nullable', 'string'],
            'priceAmount' => ['required', 'integer', 'min:0'],
            'quantityOnHand' => ['required', 'integer', 'min:0'],
        ]);

        $tagsArray = array_values(array_filter(array_map('trim', explode(',', $this->tags))));

        $product = $service->create((int) app('current_store')->getKey(), [
            'title' => $data['title'],
            'handle' => $this->handle !== '' ? $this->handle : null,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'vendor' => $this->vendor !== '' ? $this->vendor : null,
            'product_type' => $this->productType !== '' ? $this->productType : null,
            'tags' => $tagsArray,
            'price_amount' => $this->priceAmount,
            'quantity_on_hand' => $this->quantityOnHand,
        ]);

        if ($this->status !== 'draft') {
            $service->transitionStatus($product, ProductStatus::from($this->status));
        }

        session()->flash('status', 'Product created.');

        return redirect('/admin/products/'.$product->getKey().'/edit');
    }

    public function render(): View
    {
        return view('livewire.admin.products.create');
    }
}
