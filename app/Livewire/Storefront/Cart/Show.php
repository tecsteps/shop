<?php

namespace App\Livewire\Storefront\Cart;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public function render()
    {
        return view('livewire.storefront.cart.show');
    }
}
