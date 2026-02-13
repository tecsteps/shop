<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $discountCode = '';

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        if ($quantity <= 0) {
            $cartService->removeLine($cart, $lineId);
        } else {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        }

        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $cart = $this->getCart();
        $lines = $cart ? $cart->lines()->with('variant.product')->get() : collect();
        $subtotal = $lines->sum('line_total_amount');

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
        ])->layout('storefront.layouts.app', [
            'title' => 'Cart',
        ]);
    }

    private function getCart(): ?Cart
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return null;
        }

        $customer = auth('customer')->user();

        if ($customer) {
            return Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->first();
        }

        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->where('id', $cartId)
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->first();
    }
}
