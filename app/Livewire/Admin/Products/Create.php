<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Services\ProductService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Create Product')]
class Create extends Component
{
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

    public bool $handleManuallyEdited = false;

    public function updatedTitle(): void
    {
        if (! $this->handleManuallyEdited) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function updatedHandle(): void
    {
        $this->handleManuallyEdited = true;
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

        $priceAmount = $this->price !== '' ? (int) round((float) $this->price * 100) : 0;
        $compareAtAmount = $this->compareAtPrice !== '' ? (int) round((float) $this->compareAtPrice * 100) : null;

        $product = $productService->create($store, [
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'product_type' => $this->productType ?: null,
            'vendor' => $this->vendor ?: null,
            'tags' => $tagsArray,
            'status' => $this->status,
            'price_amount' => $priceAmount,
        ]);

        $defaultVariant = $product->variants()->where('is_default', true)->first();

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
                        'quantity_reserved' => 0,
                        'policy' => 'deny',
                    ],
                );
            }
        }

        $this->dispatch('toast', type: 'success', message: 'Product created successfully.');

        $this->redirect(route('admin.products.edit', $product), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.products.create', [
            'statuses' => ProductStatus::cases(),
        ]);
    }
}
