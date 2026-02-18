<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render(): View
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $order = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('order_number', $this->orderNumber)
            ->with(['lines', 'payments', 'fulfillments', 'refunds'])
            ->firstOrFail();

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ]);
    }
}
