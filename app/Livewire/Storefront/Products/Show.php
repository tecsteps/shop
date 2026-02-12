<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public int $quantity = 1;

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public ?int $selectedVariantId = null;

    public bool $adding = false;

    public function mount(): void
    {
        $store = app('current_store');

        $product = Product::where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->active()
            ->with(['variants.inventoryItem', 'variants.optionValues.option', 'options.values', 'media'])
            ->firstOrFail();

        // Pre-select the first variant's options
        $firstVariant = $product->variants->first();
        if ($firstVariant) {
            $this->selectedVariantId = $firstVariant->id;
            foreach ($firstVariant->optionValues as $optionValue) {
                $this->selectedOptions[$optionValue->option->name] = $optionValue->value;
            }
        }
    }

    public function updatedSelectedOptions(): void
    {
        $product = $this->getProduct();
        $variant = $this->findMatchingVariant($product);
        $this->selectedVariantId = $variant?->id;
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

    public function addToCart(): void
    {
        if (! $this->selectedVariantId) {
            return;
        }

        $store = app('current_store');
        $cartService = app(CartService::class);

        $cart = $cartService->getOrCreateForSession(
            $store,
            session('cart_id')
        );

        session(['cart_id' => $cart->id]);

        try {
            $cartService->addLine($cart, $this->selectedVariantId, $this->quantity);
            $itemCount = $cart->lines()->sum('quantity');
            $this->dispatch('cart-updated', cartId: $cart->id, itemCount: $itemCount);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $product = $this->getProduct();
        $selectedVariant = $this->selectedVariantId
            ? $product->variants->firstWhere('id', $this->selectedVariantId)
            : $product->variants->first();

        $stockStatus = $this->getStockStatus($selectedVariant);

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
            'stockStatus' => $stockStatus,
        ])->title($product->title);
    }

    private function getProduct(): Product
    {
        $store = app('current_store');

        return Product::where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->active()
            ->with(['variants.inventoryItem', 'variants.optionValues.option', 'options.values', 'media'])
            ->firstOrFail();
    }

    private function findMatchingVariant(Product $product): ?\App\Models\ProductVariant
    {
        foreach ($product->variants as $variant) {
            $variantOptions = [];
            foreach ($variant->optionValues as $ov) {
                $variantOptions[$ov->option->name] = $ov->value;
            }

            if ($variantOptions == $this->selectedOptions) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @return array{message: string, type: string}
     */
    private function getStockStatus(?\App\Models\ProductVariant $variant): array
    {
        if (! $variant || ! $variant->inventoryItem) {
            return ['message' => 'In stock', 'type' => 'success'];
        }

        $inventory = $variant->inventoryItem;
        $available = $inventory->available;

        if ($inventory->policy === InventoryPolicy::Deny) {
            if ($available <= 0) {
                return ['message' => 'Out of stock', 'type' => 'error'];
            }
            if ($available <= 10) {
                return ['message' => "Only {$available} left in stock", 'type' => 'warning'];
            }

            return ['message' => 'In stock', 'type' => 'success'];
        }

        if ($available <= 0) {
            return ['message' => 'Available on backorder', 'type' => 'info'];
        }

        return ['message' => 'In stock', 'type' => 'success'];
    }
}
