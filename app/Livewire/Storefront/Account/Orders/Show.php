<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $order) {
            abort(404);
        }

        $this->order = $order;
    }

    public function render(): View
    {
        $this->order->load(['lines', 'fulfillments', 'payments']);

        return view('livewire.storefront.account.orders.show', [
            'order' => $this->order,
        ]);
    }
}
