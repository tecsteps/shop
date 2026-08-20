<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $this->order = auth('customer')->user()->orders()->with('lines')->where('order_number', $orderNumber)->firstOrFail();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.orders.show')->layout('layouts.storefront');
    }
}
