<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|string')]
    public string $status = 'draft';

    #[Validate('nullable|string|max:255')]
    public string $vendor = '';

    #[Validate('nullable|string|max:255')]
    public string $type = '';

    #[Validate('nullable|string')]
    public string $tags = '';

    #[Validate('required|integer|min:0')]
    public int $price = 0;

    #[Validate('nullable|string|max:100')]
    public string $sku = '';

    #[Validate('integer|min:0')]
    public int $inventoryQuantity = 0;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            $this->title = $product->title;
            $this->description = $product->description_html ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->type = $product->product_type ?? '';
            $this->tags = implode(', ', $product->tags ?? []);

            $defaultVariant = $product->variants()->first();
            if ($defaultVariant) {
                $this->price = $defaultVariant->price_amount;
                $this->sku = $defaultVariant->sku ?? '';
                $inventoryItem = $defaultVariant->inventoryItem;
                $this->inventoryQuantity = $inventoryItem->quantity_on_hand ?? 0;
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $productService = app(ProductService::class);

        $tagsArray = $this->tags
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $data = [
            'title' => $this->title,
            'description_html' => $this->description ?: null,
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->type ?: null,
            'tags' => $tagsArray,
            'price_amount' => $this->price,
            'sku' => $this->sku ?: null,
        ];

        if ($this->product) {
            $productService->update($this->product, $data);
            $this->dispatch('toast', type: 'success', message: 'Product updated successfully.');
        } else {
            $product = $productService->create($store, $data);
            $this->redirect(route('admin.products.edit', $product), navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.products.form');
    }
}
