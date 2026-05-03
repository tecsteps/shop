<?php

namespace App\Livewire\Storefront\Cart;

use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.cart.show')
            ->layout('storefront.layouts.app', [
                'title' => 'Cart',
            ]);
    }
}
