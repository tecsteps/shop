<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public ?int $cartId = null;

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->cartId = session('cart_id');
        $this->open = true;
    }

    public function removeLine(int $lineId, CartService $carts): void
    {
        if ($this->cartId !== null) {
            $carts->removeLine(Cart::query()->findOrFail($this->cartId), $lineId);
        }
    }

    public function render(): View
    {
        $cart = $this->cartId === null ? null : Cart::query()->with('lines.variant.product')->find($this->cartId);

        return view('livewire.storefront.cart-drawer', ['cart' => $cart]);
    }
}
