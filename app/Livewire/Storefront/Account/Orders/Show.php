<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public function mount(int $orderId): void
    {
        $this->order = Auth::guard('customer')->user()->orders()
            ->whereKey($orderId)->with(['lines', 'payments', 'fulfillments'])->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.show')
            ->layout('layouts.storefront', ['title' => 'Order '.$this->order->order_number]);
    }
}
