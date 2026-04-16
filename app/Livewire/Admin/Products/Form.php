<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\HandleGenerator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public string $handle = '';

    #[Validate('required|in:draft,active,archived')]
    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $descriptionHtml = '';

    public int $priceAmount = 1999;

    public int $quantityOnHand = 100;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            $this->title = $product->title;
            $this->handle = $product->handle;
            $this->status = $product->status->value;
            $this->vendor = (string) $product->vendor;
            $this->productType = (string) $product->product_type;
            $this->descriptionHtml = (string) $product->description_html;

            $variant = $product->defaultVariant();
            if ($variant) {
                $this->priceAmount = $variant->price_amount;
                $this->quantityOnHand = $variant->inventory?->quantity_on_hand ?? 0;
            }
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $storeId = app('current_store')->id;

        if ($this->product) {
            $this->product->update([
                'title' => $this->title,
                'handle' => $this->handle ?: HandleGenerator::generate($this->title, 'products', $storeId, $this->product->id),
                'status' => $this->status,
                'vendor' => $this->vendor ?: null,
                'product_type' => $this->productType ?: null,
                'description_html' => $this->descriptionHtml ?: null,
                'published_at' => $this->status === ProductStatus::Active->value ? ($this->product->published_at ?? now()) : null,
            ]);

            $variant = $this->product->defaultVariant();
            if ($variant) {
                $variant->update(['price_amount' => $this->priceAmount]);
                if ($variant->inventory) {
                    $variant->inventory->update(['quantity_on_hand' => $this->quantityOnHand]);
                }
            }
        } else {
            $this->product = Product::create([
                'store_id' => $storeId,
                'title' => $this->title,
                'handle' => $this->handle ?: HandleGenerator::generate($this->title, 'products', $storeId),
                'status' => $this->status,
                'vendor' => $this->vendor ?: null,
                'product_type' => $this->productType ?: null,
                'description_html' => $this->descriptionHtml ?: null,
                'tags' => [],
                'published_at' => $this->status === ProductStatus::Active->value ? now() : null,
            ]);

            $variant = ProductVariant::create([
                'product_id' => $this->product->id,
                'sku' => 'SKU-'.strtoupper(substr(md5($this->product->id.time()), 0, 8)),
                'price_amount' => $this->priceAmount,
                'currency' => app('current_store')->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => 'active',
            ]);

            \App\Models\InventoryItem::create([
                'store_id' => $storeId,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $this->quantityOnHand,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);
        }

        session()->flash('success', 'Product saved.');

        return $this->redirect(route('admin.products.edit', $this->product), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.products.form')->title($this->product ? 'Edit product' : 'New product');
    }
}
