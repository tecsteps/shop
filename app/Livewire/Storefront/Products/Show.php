<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
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
            ->with(['variants.optionValues', 'variants.inventoryItem', 'options.values', 'media'])
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

    public function getIsSoldOutProperty(): bool
    {
        $variant = $this->selectedVariant;

        if (! $variant || ! $variant->inventoryItem) {
            return false;
        }

        $inventory = $variant->inventoryItem;
        $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

        return $available <= 0 && $inventory->policy === InventoryPolicy::Deny;
    }

    public function getIsBackorderProperty(): bool
    {
        $variant = $this->selectedVariant;

        if (! $variant || ! $variant->inventoryItem) {
            return false;
        }

        $inventory = $variant->inventoryItem;
        $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

        return $available <= 0 && $inventory->policy === InventoryPolicy::Continue;
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
