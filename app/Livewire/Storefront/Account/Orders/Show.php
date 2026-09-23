<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
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
        $order = Order::query()
            ->where('customer_id', auth('customer')->id())
            ->where('order_number', $this->orderNumber)
            ->with('lines', 'payments', 'fulfillments.lines')
            ->firstOrFail();

        return view('storefront.account.orders.show', ['order' => $order])->layout('storefront.layouts.app');
    }
}
