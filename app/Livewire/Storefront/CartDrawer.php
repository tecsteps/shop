<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
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
