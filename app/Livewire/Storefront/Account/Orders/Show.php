<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Order Details')]
class Show extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::where('customer_id', $customer->id)
            ->where('order_number', $this->orderNumber)
            ->with(['lines', 'payments', 'fulfillments'])
            ->firstOrFail();

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ]);
    }
}
