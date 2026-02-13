<?php

namespace App\Livewire\Storefront\Cart;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.cart.show');
    }
}
