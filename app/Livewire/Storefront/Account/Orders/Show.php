<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::where('customer_id', $customer->id)
            ->where('order_number', '#'.$this->orderNumber)
            ->with(['lines', 'fulfillments'])
            ->first();

        if (! $order) {
            abort(404);
        }

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ])->layout('layouts.storefront');
    }
}
