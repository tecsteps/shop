<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Models\CartLine;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $cart = $this->getCart();

        /** @var \Illuminate\Database\Eloquent\Collection<int, CartLine> $lines */
        $lines = $cart
            ? $cart->lines()->with('variant.product')->get()
            : collect();

        $subtotal = $lines->sum(fn (CartLine $line) => $line->unit_price_amount * $line->quantity);
        $currency = $cart->currency ?? 'EUR';

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'currency' => $currency,
        ]);
    }

    protected function getCart(): ?Cart
    {
        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        /** @var \App\Models\Store $store */
        $store = app('current_store');

        /** @var Cart|null $cart */
        $cart = Cart::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->find($cartId);

        return $cart;
    }
}
