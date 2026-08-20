<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Livewire\Component;

class Form extends Component
{
    public ?Product $product = null;

    public string $title = '';

    public string $description = '';

    public string $vendor = 'Acme';

    public string $productType = 'Apparel';

    public int $priceAmount = 0;

    public string $status = 'draft';

    public string $message = '';

    public function mount(?Product $product = null): void
    {
        $this->product = $product;

        if ($product !== null) {
            $this->title = $product->title;
            $this->description = (string) $product->description;
            $this->vendor = (string) $product->vendor;
            $this->productType = (string) $product->product_type;
            $this->status = $product->status->value;
            $this->priceAmount = (int) ($product->defaultVariant()?->price_amount ?? 0);
        }
    }

    public function save(ProductService $products): void
    {
        $data = $this->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'vendor' => ['nullable', 'string', 'max:255'], 'productType' => ['nullable', 'string', 'max:255'], 'priceAmount' => ['required', 'integer', 'min:0'], 'status' => ['required', 'in:draft,active,archived']]);
        $payload = ['title' => $data['title'], 'description' => $data['description'], 'vendor' => $data['vendor'], 'product_type' => $data['productType'], 'status' => ProductStatus::from($data['status']), 'variants' => [['title' => 'Default', 'price_amount' => $data['priceAmount'], 'is_default' => true]]];

        if ($this->product === null) {
            $this->authorize('create', Product::class);
            $this->product = $products->create(app('current_store'), $payload);
        } else {
            $this->authorize('update', $this->product);
            $products->update($this->product, $payload);
        }

        $this->message = 'Product saved';
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.form')->layout('layouts.admin');
    }
}
