<?php

namespace App\Livewire\Storefront;

use Illuminate\View\View;
use Livewire\Component;

class CartDrawer extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.cart-drawer');
    }
}
