<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Cart\CartSession;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public Product $product;

    public int $variantId;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->published()
            ->with('variants.inventory', 'variants.optionValues.option', 'options.values', 'media')
            ->where('handle', $handle)
            ->firstOrFail();

        $this->variantId = $this->product->defaultVariant()?->id ?? $this->product->variants->first()?->id ?? 0;
    }

    public function selectedVariant(): ?ProductVariant
    {
        return $this->product->variants->firstWhere('id', $this->variantId);
    }

    public function incrementQuantity(): void
    {
        $this->quantity++;
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToCart(CartSession $cartSession, CartService $cartService): void
    {
        $variant = $this->selectedVariant();
        if (! $variant) {
            $this->addError('variantId', 'Please select a variant.');

            return;
        }

        $cart = $cartSession->ensureCart();
        $cartService->addLine($cart, $variant, max(1, $this->quantity));

        $this->dispatch('cart-updated');
        $this->dispatch('notify', message: 'Added to cart.');
    }

    public function render()
    {
        $variant = $this->selectedVariant();

        return view('livewire.storefront.products.show', compact('variant'))
            ->title($this->product->title);
    }
}
