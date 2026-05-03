<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
    }

    public function render(): View
    {
        $product = Product::query()
            ->with('variants.optionValues.option', 'variants.inventoryItem', 'media')
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->firstOrFail();

        $selectedVariant = $product->variants->firstWhere('id', $this->selectedVariantId)
            ?? $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
        ])->layout('storefront.layouts.app', [
            'title' => $product->title,
        ]);
    }
}
