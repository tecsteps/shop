<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Account\CustomerComponent;
use App\Models\Order;
use Illuminate\View\View;

class Show extends CustomerComponent
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $customer = $this->authenticatedCustomer();
        $this->order = Order::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->where('customer_id', $customer->getAuthIdentifier())
            ->where('order_number', $orderNumber)
            ->with(['lines.product.media', 'payments', 'fulfillments.lines.orderLine'])
            ->firstOrFail();
    }

    public function render(): View
    {
        return $this->storefront(view('storefront.account.orders.show'), 'Order '.$this->order->order_number.' - '.$this->currentStore()->name);
    }
}
