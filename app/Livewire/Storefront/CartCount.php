<?php

namespace App\Livewire\Storefront;

use App\Models\Store;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void {}

    public function render(CartService $carts): mixed
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        abort_unless($store instanceof Store, 403);

        $cart = $carts->findActiveCart($store, auth('customer')->user());

        return view('livewire.storefront.cart-count', [
            'itemCount' => (int) ($cart?->lines->sum('quantity') ?? 0),
        ]);
    }
}
