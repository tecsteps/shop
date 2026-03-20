<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public string $orderNumber;

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('order_number', $this->orderNumber)
            ->with(['lines', 'payments', 'fulfillments.lines'])
            ->firstOrFail();

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ])->layout('layouts.storefront.app', [
            'title' => "Order {$order->order_number}",
        ]);
    }
}
