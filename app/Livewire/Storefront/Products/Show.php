<?php

namespace App\Livewire\Storefront\Products;

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

        try {
            $cart = app(CartService::class)->getOrCreateForSession(app('current_store'));
            app(CartService::class)->addLine($cart, $variant->id, $this->quantity);
        } catch (InvalidCartMutationException $exception) {
            $this->addError('quantity', $exception->getMessage());

            return;
        }

        $this->dispatch('cart-updated');
        session()->flash('cart_status', 'Added to cart.');
    }

    public function render(): View
    {
        $product = $this->productQuery()->firstOrFail();
        $selectedVariant = $this->selectedVariant($product);

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
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
}
