<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

class CartDrawer extends Component
{
    protected $listeners = ['cart-updated' => '$refresh'];

    public function render(): View
    {
        return view('livewire.storefront.cart-drawer', [
            'cart' => $this->cart(),
        ]);
    }

    private function cart(): ?Cart
    {
        $cartId = session(CartService::SESSION_KEY);

        if (! $cartId || ! app()->bound('current_store')) {
            return null;
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->whereKey($cartId)
            ->first();

        return $cart instanceof Cart ? app(CartService::class)->loadForDisplay($cart) : null;
    }
}
