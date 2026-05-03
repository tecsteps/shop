<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidCartMutationException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;

        $product = $this->productQuery()->firstOrFail();

        $this->normalizeQuantity($this->selectedVariant($product));
    }

    public function incrementQuantity(): void
    {
        $product = $this->productQuery()->firstOrFail();
        $variant = $this->selectedVariant($product);
        $stock = $this->stockState($variant);

        $this->quantity = min($stock['max_quantity'], $this->quantity + 1);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function updatedQuantity(): void
    {
        $product = $this->productQuery()->firstOrFail();

        $this->normalizeQuantity($this->selectedVariant($product));
    }

    public function addToCart(): void
    {
        $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $product = $this->productQuery()->firstOrFail();
        $variant = $this->selectedVariant($product);

        if (! $variant instanceof ProductVariant) {
            $this->addError('quantity', 'This product is not available.');

            return;
        }

        $stock = $this->stockState($variant);

        if (! $stock['can_add_to_cart']) {
            $this->addError('quantity', 'This variant is out of stock.');

            return;
        }

        $this->normalizeQuantity($variant);

        try {
            $cart = app(CartService::class)->getOrCreateForSession(app('current_store'));
            app(CartService::class)->addLine($cart, $variant->id, $this->quantity);
        } catch (InvalidCartMutationException $exception) {
            $this->addError('quantity', $exception->getMessage());

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('open-cart');
        session()->flash('cart_status', 'Added to cart.');
    }

    public function render(): View
    {
        $product = $this->productQuery()->firstOrFail();
        $selectedVariant = $this->selectedVariant($product);

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
            'stock' => $this->stockState($selectedVariant),
        ])->layout('storefront.layouts.app', [
            'title' => $product->title,
        ]);
    }

    private function productQuery(): Builder
    {
        return Product::query()
            ->with('variants.optionValues.option', 'variants.inventoryItem', 'media')
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at');
    }

    private function selectedVariant(Product $product): ?ProductVariant
    {
        return $product->variants->firstWhere('id', $this->selectedVariantId)
            ?? $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();
    }

    /**
     * @return array{available_quantity: int, max_quantity: int, can_add_to_cart: bool, message: string, tone: string}
     */
    private function stockState(?ProductVariant $variant): array
    {
        $inventoryItem = $variant?->inventoryItem;

        if (! $variant instanceof ProductVariant || ! $inventoryItem) {
            return [
                'available_quantity' => 0,
                'max_quantity' => 1,
                'can_add_to_cart' => false,
                'message' => 'Unavailable',
                'tone' => 'red',
            ];
        }

        $availableQuantity = max(0, $inventoryItem->availableQuantity());

        if ($inventoryItem->policy === InventoryPolicy::Continue && $availableQuantity < 1) {
            return [
                'available_quantity' => $availableQuantity,
                'max_quantity' => 9999,
                'can_add_to_cart' => true,
                'message' => 'Available on backorder',
                'tone' => 'blue',
            ];
        }

        if ($availableQuantity < 1) {
            return [
                'available_quantity' => 0,
                'max_quantity' => 1,
                'can_add_to_cart' => false,
                'message' => 'Out of stock',
                'tone' => 'red',
            ];
        }

        return [
            'available_quantity' => $availableQuantity,
            'max_quantity' => $inventoryItem->policy === InventoryPolicy::Deny ? min($availableQuantity, 9999) : 9999,
            'can_add_to_cart' => true,
            'message' => $availableQuantity <= 10 ? "Only {$availableQuantity} left in stock" : 'In stock',
            'tone' => $availableQuantity <= 10 ? 'amber' : 'green',
        ];
    }

    private function normalizeQuantity(?ProductVariant $variant): void
    {
        $stock = $this->stockState($variant);

        $this->quantity = min(
            $stock['max_quantity'],
            max(1, $this->quantity),
        );
    }
}
