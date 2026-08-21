<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public Cart $cart;

    public function mount(CartService $carts): void
    {
        $this->cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
    }

    #[On('open-cart-drawer')]
    public function open(): void
    {
        $this->refreshCart();
        $this->open = true;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->cart = app(CartService::class)->getOrCreateForSession(app('current_store'), auth('customer')->user());
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart-drawer');
    }
}
