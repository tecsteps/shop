<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public int $selectedVariantId;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->product = Product::published()->where('handle', $handle)
            ->with(['options.values', 'variants.optionValues.option', 'variants.inventoryItem', 'media'])
            ->firstOrFail();
        $this->selectedVariantId = $this->product->variants->firstWhere('is_default', true)?->id
            ?? $this->product->variants->firstOrFail()->id;
    }

    public function addToCart(CartService $carts): void
    {
        $this->validate(['selectedVariantId' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1']]);
        $variant = $this->product->variants->firstWhere('id', $this->selectedVariantId);
        abort_unless($variant instanceof ProductVariant, 404);
        $customer = auth('customer')->user();
        $cart = $carts->getOrCreateForSession(app('current_store'), $customer);
        $carts->addLine($cart, $variant->id, $this->quantity);
        session()->flash('storefront_status', 'Added to cart');
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show', [
            'selectedVariant' => $this->product->variants->firstWhere('id', $this->selectedVariantId),
        ])->layout('layouts.storefront', ['title' => $this->product->title.' - '.app('current_store')->name]);
    }
}
