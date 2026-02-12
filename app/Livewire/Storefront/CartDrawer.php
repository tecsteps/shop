<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        $this->open = true;
    }

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        try {
            if ($quantity <= 0) {
                $cartService->removeLine($cart, $lineId);
            } else {
                $cartService->updateLineQuantity($cart, $lineId, $quantity);
            }
            $itemCount = $cart->fresh()->lines()->sum('quantity');
            $this->dispatch('cart-updated', cartId: $cart->id, itemCount: $itemCount);
        } catch (\Exception) {
            // Silently handle
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $itemCount = $cart->fresh()->lines()->sum('quantity');
        $this->dispatch('cart-updated', cartId: $cart->id, itemCount: $itemCount);
    }

    public function proceedToCheckout(): void
    {
        $cart = $this->getCart();
        if (! $cart || $cart->lines->isEmpty()) {
            return;
        }

        $store = app('current_store');
        $checkoutService = app(CheckoutService::class);
        $checkout = $checkoutService->createFromCart($cart, $store);

        $this->redirect(route('storefront.checkout.show', $checkout->id));
    }

    public function render()
    {
        $cart = $this->getCart();
        $lines = $cart ? $cart->lines()->with('variant.product.media')->get() : collect();
        $subtotal = $lines->sum('line_subtotal_amount');
        $itemCount = $lines->sum('quantity');

        return view('livewire.storefront.cart-drawer', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
        ]);
    }

    private function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::with('lines')->find($cartId);
    }
}
