<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $customer = Auth::guard('customer')->user();

        $this->order = Order::withoutGlobalScopes()
            ->where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->where('store_id', app('current_store')->id)
            ->with(['lines', 'payments', 'fulfillments'])
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.show', [
            'order' => $this->order,
        ])->layout('storefront.layouts.app', [
            'title' => 'Order '.$this->order->order_number,
        ]);
    }
}
