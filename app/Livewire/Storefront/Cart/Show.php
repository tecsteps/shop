<?php

namespace App\Livewire\Storefront\Cart;

use Livewire\Component;

class Show extends Component
{
    public function render(): mixed
    {
        return view('livewire.storefront.cart.show')
            ->layout('layouts::storefront');
    }
}
