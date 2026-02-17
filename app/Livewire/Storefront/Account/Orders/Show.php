<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public string $orderNumber;

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $customer->store_id)
            ->where('customer_id', $customer->id)
            ->where('order_number', $this->orderNumber)
            ->with(['lines', 'fulfillments.lines', 'payments'])
            ->firstOrFail();

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ])->title("Order {$order->order_number}");
    }
}
