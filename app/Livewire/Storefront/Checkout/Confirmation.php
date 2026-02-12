<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Order Confirmed')]
class Confirmation extends Component
{
    public string $checkoutId = '';

    public function render()
    {
        $checkout = Checkout::where('id', $this->checkoutId)
            ->where('status', CheckoutStatus::Completed)
            ->firstOrFail();

        $order = Order::where('checkout_id', $checkout->id)
            ->with(['lines', 'payments'])
            ->firstOrFail();

        return view('livewire.storefront.checkout.confirmation', [
            'checkout' => $checkout,
            'order' => $order,
        ]);
    }
}
