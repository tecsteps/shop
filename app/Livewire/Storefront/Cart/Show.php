<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Full cart page: line items, quantity controls, subtotal, and a checkout link.
 *
 * Like {@see \App\Livewire\Storefront\CartDrawer} this routes every mutation
 * through {@see CartService}. Storefront teammate owns the final layout (task
 * #6); this is the functional backing component.
 */
#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public function updateQuantity(int $lineId, int $quantity): void
    {
        app(CartService::class)->updateLineQuantity($this->cart(), $lineId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function remove(int $lineId): void
    {
        app(CartService::class)->removeLine($this->cart(), $lineId);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $cart = $this->cart()->load('lines.variant.product');

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $cart->lines,
            'subtotal' => $cart->subtotalAmount(),
        ]);
    }

    private function cart(): Cart
    {
        return app(CartService::class)->getOrCreateForSession(
            app('current_store'),
            Auth::guard('customer')->user(),
        );
    }
}
