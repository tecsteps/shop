<?php

namespace App\Livewire\Storefront\Checkout;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Confirmation extends Component
{
    public string $number = '';

    public function mount(string $number): void
    {
        $this->number = $number;
    }

    public function render()
    {
        return view('livewire.storefront.checkout.confirmation');
    }
}
