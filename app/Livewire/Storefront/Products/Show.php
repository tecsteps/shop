<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public ?string $cartError = null;

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
        $this->cartError = null;

        if (! $this->selectedVariantId) {
            return;
        }

        try {
            $store = app('current_store');
            $cartService = app(CartService::class);
            $cart = $cartService->getOrCreateForSession($store);
            $cartService->addLine($cart, $this->selectedVariantId, $this->quantity);
            $this->dispatch('cart-updated');
        } catch (\App\Exceptions\InsufficientInventoryException $e) {
            $this->cartError = 'Not enough stock available.';
        } catch (\App\Exceptions\InvalidCartException $e) {
            $this->cartError = $e->getMessage();
        }
    }

    public function render(): mixed
    {
        return view('livewire.storefront.products.show')
            ->layout('layouts::storefront');
    }
}
