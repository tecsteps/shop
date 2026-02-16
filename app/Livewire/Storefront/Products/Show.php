<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle;

    public int $quantity = 1;

    /** @var array<string, int> */
    public array $selectedOptions = [];

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with(['variants.optionValues.option', 'options.values'])
            ->first();

        if (! $product) {
            abort(404);
        }

        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        if ($defaultVariant) {
            $this->selectedVariantId = $defaultVariant->id;
            foreach ($defaultVariant->optionValues as $optionValue) {
                $this->selectedOptions[$optionValue->product_option_id] = $optionValue->id;
            }
        }
    }

    public function updatedSelectedOptions(): void
    {
        $product = Product::query()
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->with('variants.optionValues')
            ->first();

        if (! $product) {
            return;
        }

        $selectedValueIds = collect($this->selectedOptions)->values()->filter()->sort()->values();

        foreach ($product->variants as $variant) {
            $variantValueIds = $variant->optionValues->pluck('id')->sort()->values();
            if ($variantValueIds->toArray() === $selectedValueIds->toArray()) {
                $this->selectedVariantId = $variant->id;

                return;
            }
        }
    }

    public function addToCart(): void
    {
        $product = Product::query()
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->first();

        if (! $product) {
            return;
        }

        $variantId = $this->selectedVariantId;
        if (! $variantId) {
            $variant = $product->variants()->where('is_default', true)->first() ?? $product->variants()->first();
            $variantId = $variant?->id;
        }

        if (! $variantId) {
            return;
        }

        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);

        try {
            $cartService->addLine($cart, $variantId, $this->quantity);
        } catch (\App\Exceptions\InsufficientInventoryException $e) {
            session()->flash('error', 'Not enough stock available. Only '.$e->available.' items left.');

            return;
        }

        session()->flash('success', 'Added to cart!');
        $this->dispatch('cart-updated');
    }

    public function incrementQuantity(): void
    {
        $this->quantity++;
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function render(): mixed
    {
        $product = Product::query()
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->with(['variants.optionValues.option', 'variants.inventoryItem', 'options.values', 'media'])
            ->first();

        if (! $product) {
            abort(404);
        }

        $selectedVariant = $this->selectedVariantId
            ? $product->variants->firstWhere('id', $this->selectedVariantId)
            : ($product->variants->firstWhere('is_default', true) ?? $product->variants->first());

        $inventoryItem = $selectedVariant?->inventoryItem;
        $quantityAvailable = $inventoryItem !== null ? $inventoryItem->quantity_available : 0;
        $inventoryPolicy = $inventoryItem !== null ? $inventoryItem->policy : InventoryPolicy::Deny;

        if ($quantityAvailable > 0) {
            $stockStatus = 'in_stock';
        } elseif ($inventoryPolicy === InventoryPolicy::Continue) {
            $stockStatus = 'backorder';
        } else {
            $stockStatus = 'sold_out';
        }

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
            'stockStatus' => $stockStatus,
        ]);
    }
}
