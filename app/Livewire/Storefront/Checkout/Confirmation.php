<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use Livewire\Component;

class Confirmation extends Component
{
    public Checkout $checkout;

    public function mount(int $checkoutId): void
    {
        $checkout = Checkout::with('cart.lines.variant.product')->find($checkoutId);

        if (! $checkout) {
            abort(404);
        }

        $this->checkout = $checkout;
    }

    public function render(): mixed
    {
        $cart = $this->checkout->cart;
        $lines = $cart->lines;
        $currency = $cart->currency;
        $totals = $this->checkout->totals_json;

        return view('livewire.storefront.checkout.confirmation', [
            'lines' => $lines,
            'currency' => $currency,
            'totals' => $totals,
        ])->layout('layouts.storefront', ['title' => 'Order Confirmation']);
    }
}
