<?php

namespace App\Livewire\Storefront;

use App\Enums\CartStatus;
use App\Models\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->loadCount();
    }

    #[On('cart-updated')]
    public function loadCount(): void
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            $this->count = 0;

            return;
        }

        $cart = Cart::where('id', $cartId)
            ->where('status', CartStatus::Active)
            ->with('lines')
            ->first();

        $this->count = $cart ? $cart->lines->sum('quantity') : 0;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart-count');
    }
}
