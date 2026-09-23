<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Livewire\Component;

class Confirmation extends Component
{
    public int $checkoutId;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
    }

    public function render(): mixed
    {
        $order = Order::query()->with('lines', 'payments')->where('checkout_id', $this->checkoutId)->firstOrFail();

        return view('storefront.checkout.confirmation', ['order' => $order])->layout('storefront.layouts.app');
    }
}
