<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Edit Product')]
class Edit extends Component
{
    public Product $product;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $productType = '';

    public string $vendor = '';

    public string $tags = '';

    public string $status = 'draft';

    public string $price = '';

    public string $compareAtPrice = '';

    public string $sku = '';

    public string $barcode = '';

    public bool $trackQuantity = true;

    public int $quantity = 0;

    public bool $showDeleteModal = false;

    public function mount(Product $product): void
    {
        $store = app('current_store');

        abort_unless($product->store_id === $store->id, 404);

        $this->product = $product->load(['variants.inventoryItem', 'media']);

        $this->title = $product->title;
        $this->handle = $product->handle;
        $this->descriptionHtml = $product->description_html ?? '';
        $this->productType = $product->product_type ?? '';
        $this->vendor = $product->vendor ?? '';
        $this->tags = is_array($product->tags) ? implode(', ', $product->tags) : '';
        $this->status = $product->status->value;

        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        if ($defaultVariant) {
            $this->price = $defaultVariant->price_amount > 0
                ? number_format($defaultVariant->price_amount / 100, 2, '.', '')
                : '';
            $this->compareAtPrice = $defaultVariant->compare_at_amount
                ? number_format($defaultVariant->compare_at_amount / 100, 2, '.', '')
                : '';
            $this->sku = $defaultVariant->sku ?? '';
            $this->barcode = $defaultVariant->barcode ?? '';

            if ($defaultVariant->inventoryItem) {
                $this->trackQuantity = true;
                $this->quantity = $defaultVariant->inventoryItem->quantity_on_hand;
            }
        }
    }

    public function save(ProductService $productService): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'productType' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'quantity' => ['integer', 'min:0'],
        ]);

        $store = app('current_store');

        $tagsArray = $this->tags
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $productService->update($this->product, [
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'product_type' => $this->productType ?: null,
            'vendor' => $this->vendor ?: null,
            'tags' => $tagsArray,
            'status' => $this->status,
        ]);

        $priceAmount = $this->price !== '' ? (int) round((float) $this->price * 100) : 0;
        $compareAtAmount = $this->compareAtPrice !== '' ? (int) round((float) $this->compareAtPrice * 100) : null;

        $defaultVariant = $this->product->variants()->where('is_default', true)->first()
            ?? $this->product->variants()->first();

        if ($defaultVariant) {
            $defaultVariant->update([
                'price_amount' => $priceAmount,
                'compare_at_amount' => $compareAtAmount,
                'sku' => $this->sku ?: null,
                'barcode' => $this->barcode ?: null,
            ]);

            if ($this->trackQuantity) {
                $defaultVariant->inventoryItem()->updateOrCreate(
                    ['variant_id' => $defaultVariant->id],
                    [
                        'store_id' => $store->id,
                        'quantity_on_hand' => $this->quantity,
                        'quantity_reserved' => $defaultVariant->inventoryItem?->quantity_reserved ?? 0,
                        'policy' => 'deny',
                    ],
                );
            }
        }

        $this->product->refresh()->load(['variants.inventoryItem', 'media']);

        $this->dispatch('toast', type: 'success', message: 'Product updated successfully.');
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteProduct(ProductService $productService): void
    {
        try {
            $productService->delete($this->product);

            $this->dispatch('toast', type: 'success', message: 'Product deleted successfully.');

            $this->redirect(route('admin.products.index'), navigate: true);
        } catch (InvalidArgumentException $e) {
            $this->showDeleteModal = false;
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.admin.products.edit', [
            'statuses' => ProductStatus::cases(),
            'variants' => $this->product->variants()->with('inventoryItem')->orderBy('position')->get(),
        ]);
    }
}
