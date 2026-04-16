<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Confirmation extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $this->order = Order::query()
            ->with('lines')
            ->where('order_number', '#'.$orderNumber)
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.storefront.checkout.confirmation')->title('Thank you');
    }
}
