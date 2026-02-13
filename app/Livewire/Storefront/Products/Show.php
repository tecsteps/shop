<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    /**
     * @var array<string, string>
     */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with([
                'variants.inventoryItem',
                'options.values',
                'media',
            ])
            ->firstOrFail();

        $defaultVariant = $this->product->variants->firstWhere('is_default', true)
            ?? $this->product->variants->first();

        if ($defaultVariant) {
            $this->selectedVariantId = $defaultVariant->id;
        }
    }

    public function addToCart(): void
    {
        if (! $this->selectedVariantId) {
            return;
        }

        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return;
        }

        $cartService = app(CartService::class);
        $customer = auth('customer')->user();
        $cart = $cartService->getOrCreateForSession($store, $customer);

        try {
            $cartService->addLine($cart, $this->selectedVariantId, $this->quantity);
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->addError('cart', $e->getMessage());
        }
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

    public function render(): View
    {
        $selectedVariant = $this->selectedVariantId
            ? $this->product->variants->find($this->selectedVariantId)
            : $this->product->variants->first();

        $primaryCollection = $this->product->collections->first();

        return view('livewire.storefront.products.show', [
            'selectedVariant' => $selectedVariant,
            'primaryCollection' => $primaryCollection,
        ])->layout('storefront.layouts.app', [
            'title' => $this->product->title,
        ]);
    }
}
