<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 404);

        $this->order = $order->load(['lines.variant.product.media', 'payments', 'fulfillments.lines']);
    }

    public function render()
    {
        return view('livewire.storefront.account.orders.show')
            ->layout('layouts.storefront')
            ->title('Order '.$this->order->order_number.' - '.app('current_store')->name);
    }
}
