<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\ManagesCart;
use Illuminate\View\View;
use Livewire\Attributes\On;

class CartDrawer extends StorefrontComponent
{
    use ManagesCart;

    public bool $open = false;

    public function mount(): void
    {
        $this->initializeCartDiscount();
    }

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        unset($this->cart, $this->cartSubtotal, $this->cartTotal, $this->cartItemCount);
        $this->open = true;
    }

    #[On('close-cart-drawer')]
    public function closeDrawer(): void
    {
        $this->open = false;
    }

    #[On('cart-updated')]
    public function cartUpdated(): void
    {
        unset($this->cart, $this->cartSubtotal, $this->cartTotal, $this->cartItemCount);
        $this->open = true;
    }

    public function render(): View
    {
        return view('storefront.components.cart-drawer');
    }
}
