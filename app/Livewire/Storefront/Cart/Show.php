<?php

namespace App\Livewire\Storefront\Cart;

use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cartService = app(CartService::class);
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        $cartService->updateLineQuantity($cart, $lineId, $quantity);
    }

    public function removeLine(int $lineId): void
    {
        $cartService = app(CartService::class);
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        $cartService->removeLine($cart, $lineId);
    }

    public function render(): \Illuminate\View\View
    {
        $cart = $this->getCart();
        $lines = $cart ? $cart->lines()->with('variant.product')->get() : collect();
        $subtotal = $lines->sum('line_total_amount');

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
        ])->layout('layouts.storefront.app', [
            'title' => 'Cart',
        ]);
    }

    protected function getCart(): ?\App\Models\Cart
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');
        $cartService = app(CartService::class);
        $customer = auth('customer')->user();

        $cartId = session('cart_id');

        if ($customer) {
            return \App\Models\Cart::where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', \App\Enums\CartStatus::Active)
                ->first();
        }

        if ($cartId) {
            return \App\Models\Cart::where('id', $cartId)
                ->where('store_id', $store->id)
                ->where('status', \App\Enums\CartStatus::Active)
                ->first();
        }

        return null;
    }
}
