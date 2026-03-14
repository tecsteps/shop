<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $customer = Auth::guard('customer')->user();

        $this->order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with(['lines', 'fulfillments.lines'])
            ->firstOrFail();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.orders.show')
            ->layout('layouts.storefront');
    }
}
