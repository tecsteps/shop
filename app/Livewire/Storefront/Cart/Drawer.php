<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart as CartModel;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Drawer extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    #[On('cart-updated')]
    public function refreshDrawer(): void
    {
        // Livewire re-renders when the event fires.
    }

    public function render(): View
    {
        $cartId = session('cart_id');
        $cart = $cartId ? CartModel::query()->find($cartId) : null;

        return view('livewire.storefront.cart.drawer', [
            'cart' => $cart,
            'lines' => $cart ? $cart->lines()->with('variant.product')->get() : collect(),
        ]);
    }
}
