<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use Livewire\Component;

class Confirmation extends Component
{
    public Checkout $checkout;

    public function mount(Checkout $checkout): void
    {
        $this->checkout = $checkout->load('cart.lines.variant.product');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.confirmation', [
            'checkout' => $this->checkout,
            'totals' => $this->checkout->totals_json ?? [],
        ])->layout('layouts::storefront');
    }
}
