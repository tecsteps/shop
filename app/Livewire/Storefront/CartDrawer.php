<?php

namespace App\Livewire\Storefront;

use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function render()
    {
        return view('livewire.storefront.cart-drawer');
    }
}
