<?php

namespace App\Livewire\Storefront;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public string $discountCode = '';

    public string $discountError = '';

    public string $discountSuccess = '';

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Livewire re-renders automatically
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        try {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->dispatch('cart-updated');
    }

    public function removeItem(int $lineId): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);
        $cartService->removeLine($cart, $lineId);

        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $this->discountError = '';
        $this->discountSuccess = '';

        $cart = $this->getCart();
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $cart || ! $store || empty(trim($this->discountCode))) {
            return;
        }

        try {
            $discountService = app(DiscountService::class);
            $discountService->validate($this->discountCode, $store, $cart);
            $this->discountSuccess = 'Discount code applied.';
        } catch (\App\Exceptions\InvalidDiscountException $e) {
            $this->discountError = match ($e->reasonCode) {
                'discount_not_found' => 'Invalid discount code.',
                'discount_expired' => 'This discount has expired.',
                'discount_not_yet_active' => 'This discount is not yet active.',
                'discount_usage_limit_reached' => 'This discount has reached its usage limit.',
                'discount_min_purchase_not_met' => 'Minimum purchase amount not met.',
                'discount_not_applicable' => 'This discount does not apply to your cart items.',
                default => 'Invalid discount code.',
            };
        }
    }

    public function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::where('id', $cartId)
            ->where('status', CartStatus::Active)
            ->with(['lines.variant.product.media' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->first();
    }

    public function render(): mixed
    {
        $cart = $this->getCart();
        $lines = $cart?->lines ?? collect();
        $subtotal = $lines->sum('line_total_amount');
        $itemCount = $lines->sum('quantity');
        $currency = $cart?->currency ?? 'EUR';

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'currency' => $currency,
        ]);
    }
}
