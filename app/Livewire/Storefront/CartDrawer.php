<?php

namespace App\Livewire\Storefront;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    #[On('close-cart-drawer')]
    public function closeDrawer(): void
    {
        $this->open = false;
    }

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        $this->open = true;
    }

    public function render(): View
    {
        return view('livewire.storefront.cart-drawer');
    }
}
