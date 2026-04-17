<?php

namespace App\Livewire\Storefront\Checkout;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Success extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.checkout.success');
    }
}
