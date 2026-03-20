<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    public string $addedMessage = '';

    public string $errorMessage = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $product = $this->getProduct();
        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        $this->selectedVariantId = $defaultVariant?->id;
    }

    public function addToCart(): void
    {
        $this->addedMessage = '';
        $this->errorMessage = '';

        $store = app('current_store');
        $cartService = app(CartService::class);
        $customer = auth('customer')->user();

        $cart = $cartService->getOrCreateForSession($store, $customer);

        try {
            $cartService->addLine($cart, $this->selectedVariantId, $this->quantity);
            $this->addedMessage = 'Added to cart!';
        } catch (InsufficientInventoryException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): \Illuminate\View\View
    {
        $product = $this->getProduct();

        $defaultVariant = $product->variants->firstWhere('id', $this->selectedVariantId)
            ?? $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'defaultVariant' => $defaultVariant,
        ])->layout('layouts.storefront.app', [
            'title' => $product->title,
        ]);
    }

    protected function getProduct(): Product
    {
        return Product::query()
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->with(['variants.inventoryItem', 'media', 'options.values'])
            ->firstOrFail();
    }
}
