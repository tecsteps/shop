<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
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

    public function close(): void
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
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        } catch (\Exception) {
            // Silently handle errors in drawer
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);
        $cartService->removeLine($cart, $lineId);
    }

    public function getCart(): ?Cart
    {
        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->with('lines.variant.product')
            ->find($cartId);
    }

    public function render(): mixed
    {
        $cart = $this->getCart();

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'lines' => $cart ? $cart->lines : collect(),
            'subtotal' => $cart ? $cart->lines->sum('line_total_amount') : 0,
        ]);
    }
}
