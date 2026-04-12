<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Support\CartSession;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    use EnsuresStore;

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Triggers re-render when cart changes elsewhere.
    }

    public function render(): View
    {
        $this->ensureCurrentStore();

        $cart = CartSession::current();
        $cart?->load('lines.variant.product');

        $count = 0;
        $subtotal = 0;
        if ($cart !== null) {
            foreach ($cart->lines as $line) {
                $count += (int) $line->quantity;
                $subtotal += (int) $line->line_subtotal_amount;
            }
        }

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'count' => $count,
            'subtotal' => $subtotal,
        ]);
    }
}
