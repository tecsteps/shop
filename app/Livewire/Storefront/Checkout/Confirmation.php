<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use Illuminate\View\View;
use Livewire\Component;

class Confirmation extends Component
{
    public Checkout $checkout;

    public function mount(int $checkout): void
    {
        $this->checkout = Checkout::withoutGlobalScopes()
            ->findOrFail($checkout);
    }

    public function render(): View
    {
        $cart = $this->checkout->cart()->with('lines.variant.product')->first();

        return view('livewire.storefront.checkout.confirmation', [
            'checkout' => $this->checkout,
            'cart' => $cart,
        ])->layout('storefront.layouts.app', [
            'title' => 'Order Confirmed',
        ]);
    }
}
