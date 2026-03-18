<?php

namespace App\Livewire\Storefront\Checkout;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Order Confirmation')]
class Confirmation extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.checkout.confirmation')
            ->layout('storefront.layouts.app', ['title' => 'Order Confirmation']);
    }
}
