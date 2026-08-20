<?php

namespace App\Livewire\Storefront\Products;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public int $selectedVariantId;

    public int $quantity = 1;

    public string $message = '';

    public function mount(string $handle): void
    {
        $this->product = Product::query()->with(['variants.inventory', 'media', 'options.values'])->where('handle', $handle)->firstOrFail();
        abort_unless($this->product->status->value === 'active', 404);
        $this->selectedVariantId = $this->product->defaultVariant()?->getKey() ?? 0;
    }

    public function addToCart(CartService $carts): void
    {
        $cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
        try {
            $carts->addLine($cart, $this->selectedVariantId, $this->quantity);
        } catch (InsufficientInventoryException $exception) {
            $this->addError('quantity', $exception->getMessage());

            return;
        }
        $this->message = 'Added to cart';
        $this->dispatch('cart-updated');
    }

    public function selectVariant(int $variantId): void
    {
        abort_unless($this->product->variants->contains('id', $variantId), 404);

        $this->selectedVariantId = $variantId;
    }

    public function render(): mixed
    {
        $this->product->loadMissing(['variants.inventory', 'media', 'options.values']);

        return view('livewire.storefront.products.show')->layout('layouts.storefront');
    }
}
