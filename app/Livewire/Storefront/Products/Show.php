<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public Product $product;

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with(['variants.inventoryItem', 'options.values', 'media'])
            ->first();

        if (! $product) {
            abort(404);
        }

        $this->product = $product;

        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        $this->selectedVariantId = $defaultVariant?->id;
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
        $this->quantity = 1;
    }

    public function addToCart(): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);

        try {
            $cartService->addLine($cart, $this->selectedVariantId, $this->quantity);
            $this->dispatch('cart-updated');
            session()->flash('cart-success', 'Added to cart!');
        } catch (InsufficientInventoryException $e) {
            session()->flash('cart-error', "Not enough stock available. Only {$e->available} left.");
        }
    }

    public function getSelectedVariantProperty(): ?ProductVariant
    {
        if (! $this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->find($this->selectedVariantId);
    }

    public function getStockStatusProperty(): string
    {
        $variant = $this->selectedVariant;

        if (! $variant) {
            return 'In stock';
        }

        $inventoryItem = $variant->inventoryItem;

        if (! $inventoryItem) {
            return 'In stock';
        }

        $available = $inventoryItem->available();

        if ($inventoryItem->policy === InventoryPolicy::Continue && $available <= 0) {
            return 'Available on backorder';
        }

        if ($inventoryItem->policy === InventoryPolicy::Deny) {
            if ($available <= 0) {
                return 'Sold out';
            }

            if ($available < 5) {
                return "Low stock - only {$available} left";
            }
        }

        return 'In stock';
    }

    public function getCanAddToCartProperty(): bool
    {
        if (! $this->selectedVariantId) {
            return false;
        }

        $variant = $this->selectedVariant;

        if (! $variant) {
            return false;
        }

        $inventoryItem = $variant->inventoryItem;

        if ($inventoryItem && $inventoryItem->policy === InventoryPolicy::Deny && $inventoryItem->available() <= 0) {
            return false;
        }

        return true;
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show');
    }
}
