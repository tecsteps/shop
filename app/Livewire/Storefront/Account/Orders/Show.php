<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $storeId;

    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        $store = app('current_store');
        $customer = Auth::guard('customer')->user();

        abort_unless($store instanceof Store && $customer instanceof Customer, 404);

        $order = Order::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('customer_id', $customer->getKey())
            ->whereKey($order->getKey())
            ->first();

        abort_unless($order instanceof Order, 404);

        $this->storeId = $store->getKey();
        $this->orderId = $order->getKey();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.orders.show', [
            'customer' => $this->customer(),
            'order' => $this->order(),
        ])->layout('layouts.storefront', [
            'title' => 'Order details',
        ]);
    }

    private function order(): Order
    {
        return Order::withoutGlobalScopes()
            ->with(['lines', 'payments', 'refunds', 'fulfillments.lines'])
            ->where('store_id', $this->storeId)
            ->where('customer_id', Auth::guard('customer')->id())
            ->findOrFail($this->orderId);
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof Customer, 403);

        return Customer::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey($customer->getKey())
            ->firstOrFail();
    }
}
