<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Confirmation extends Component
{
    public string $number = '';

    public ?Order $order = null;

    public function mount(string $number): void
    {
        $this->number = $number;

        $this->order = Order::query()
            ->where('store_id', app('current_store')->id)
            ->where('order_number', $number)
            ->with(['lines', 'payments'])
            ->first();
    }

    public function render()
    {
        return view('livewire.storefront.checkout.confirmation');
    }
}
