<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->where('handle', $handle)
            ->where('status', 'active')
            ->with(['variants', 'options.values', 'media'])
            ->firstOrFail();

        $defaultVariant = $this->product->variants->first();

        if ($defaultVariant) {
            $this->selectedVariantId = $defaultVariant->id;
        }
    }

    public function getSelectedVariantProperty(): ?ProductVariant
    {
        if (! $this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
        $this->quantity = 1;
    }

    public function addToCart(): void
    {
        // Placeholder - cart logic will be implemented in Phase 4
        $this->dispatch('cart-updated');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.products.show')
            ->layout('layouts::storefront');
    }
}
