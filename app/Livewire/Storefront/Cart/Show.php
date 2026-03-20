<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public ?string $discountCode = '';

    public ?string $discountError = null;

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        try {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
            $this->dispatch('cart-updated');
        } catch (\App\Exceptions\InsufficientInventoryException) {
            session()->flash('error', 'Not enough stock available.');
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
        $this->dispatch('cart-updated');
    }

    public function proceedToCheckout(): mixed
    {
        return $this->redirect(route('storefront.checkout'));
    }

    private function getCart(): ?Cart
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

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $cart ? $cart->lines : collect(),
            'subtotal' => $cart ? $cart->lines->sum('line_total_amount') : 0,
        ])->layout('layouts::storefront');
    }
}
