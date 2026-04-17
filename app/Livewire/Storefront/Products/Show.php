<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Store;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    public string $addedMessage = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with(['variants' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        if ($defaultVariant) {
            $this->selectedVariantId = $defaultVariant->id;
        }
    }

    public function addToCart(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->first();

        if (! $product || ! $this->selectedVariantId) {
            return;
        }

        $cartService = app(CartService::class);

        $cart = $this->getOrCreateCart($store, $cartService);
        $cartService->addLine($cart, $this->selectedVariantId, $this->quantity);

        session(['cart_id' => $cart->id]);
        $this->addedMessage = 'Added to cart!';
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min($this->quantity + 1, 99);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max($this->quantity - 1, 1);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        /** @var Store $store */
        $store = app('current_store');

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->with([
                'variants' => fn ($q) => $q->orderBy('position'),
                'variants.optionValues.option',
                'options.values',
            ])
            ->firstOrFail();

        $selectedVariant = $product->variants->firstWhere('id', $this->selectedVariantId)
            ?? $product->variants->first();

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
        ]);
    }

    protected function getOrCreateCart(Store $store, CartService $cartService): Cart
    {
        $cartId = session('cart_id');

        if ($cartId) {
            /** @var Cart|null $cart */
            $cart = Cart::query()->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->find($cartId);
            if ($cart) {
                return $cart;
            }
        }

        return $cartService->create($store);
    }
}
