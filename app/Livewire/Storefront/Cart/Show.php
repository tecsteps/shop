<?php

namespace App\Livewire\Storefront\Cart;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public function render(): mixed
    {
        return view('livewire.storefront.cart.show');
    }
}
