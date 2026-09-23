<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Form extends Component
{
    public ?int $productId = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $vendor = '';

    public string $productType = '';

    public int $priceAmount = 0;

    public ?int $compareAtAmount = null;

    public int $quantityOnHand = 0;

    public string $sku = '';

    public string $status = 'draft';

    public function mount(?int $product = null): void
    {
        if ($product) {
            $saved = Product::query()->with('variants.inventoryItem')->findOrFail($product);
            Gate::authorize('update', $saved);
            $variant = $saved->variants->first();
            $this->productId = $saved->id;
            $this->title = $saved->title;
            $this->handle = $saved->handle;
            $this->descriptionHtml = strip_tags($saved->description_html ?? '');
            $this->vendor = $saved->vendor ?? '';
            $this->productType = $saved->product_type ?? '';
            $this->status = $saved->status;
            $this->priceAmount = (int) ($variant?->price_amount ?? 0);
            $this->compareAtAmount = $variant?->compare_at_amount;
            $this->quantityOnHand = (int) ($variant?->inventoryItem?->quantity_on_hand ?? 0);
            $this->sku = $variant?->sku ?? '';
        } else {
            Gate::authorize('create', Product::class);
        }
    }

    public function save(ProductService $products): mixed
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'priceAmount' => ['required', 'integer', 'min:0'],
            'compareAtAmount' => ['nullable', 'integer', 'min:0'],
            'quantityOnHand' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:draft,active,archived'],
        ]);

        if ($this->productId) {
            $product = Product::query()->findOrFail($this->productId);
            Gate::authorize('update', $product);
            $product = $products->update($product, [
                'title' => $this->title,
                'handle' => $this->handle,
                'description_html' => $this->descriptionHtml,
                'vendor' => $this->vendor,
                'product_type' => $this->productType,
            ]);
            $variant = $product->variants()->firstOrFail();
            $variant->update(['price_amount' => $this->priceAmount, 'compare_at_amount' => $this->compareAtAmount, 'sku' => $this->sku ?: null]);
            $variant->inventoryItem()->update(['quantity_on_hand' => $this->quantityOnHand]);

            if ($product->status !== $this->status) {
                Gate::authorize($this->status === 'archived' ? 'archive-products' : 'manage-products');
                $products->transitionStatus($product, ProductStatus::from($this->status));
            }
        } else {
            $store = app('current_store');
            abort_unless($store instanceof Store, 404);
            $product = $products->create($store, [
                'title' => $this->title,
                'handle' => $this->handle ?: $this->title,
                'description_html' => e($this->descriptionHtml),
                'vendor' => $this->vendor ?: null,
                'product_type' => $this->productType ?: null,
                'status' => 'draft',
                'variants' => [[
                    'sku' => $this->sku ?: null,
                    'price_amount' => $this->priceAmount,
                    'compare_at_amount' => $this->compareAtAmount,
                    'quantity_on_hand' => $this->quantityOnHand,
                    'is_default' => true,
                    'requires_shipping' => true,
                ]],
            ]);

            if ($this->status !== 'draft') {
                Gate::authorize($this->status === 'archived' ? 'archive-products' : 'manage-products');
                $products->transitionStatus($product, ProductStatus::from($this->status));
            }
        }

        session()->flash('status', 'Product saved.');

        return $this->redirectRoute('admin.products.edit', ['product' => $product->id]);
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.form', ['isEditing' => $this->productId !== null])->layout('layouts.admin');
    }
}
