<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Confirmation extends Component
{
    #[Locked]
    public int $storeId;

    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $order = Order::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereKey($order->getKey())
            ->first();

        abort_unless($order instanceof Order, 404);

        $customer = Auth::guard('customer')->user();
        $sessionOrderId = session('last_order_id');
        $isCustomerOrder = $customer instanceof Customer && $order->customer_id === $customer->getKey();
        $isSessionOrder = $sessionOrderId !== null && (int) $sessionOrderId === (int) $order->getKey();

        abort_unless($isCustomerOrder || $isSessionOrder, 404);

        $this->storeId = $store->getKey();
        $this->orderId = $order->getKey();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.confirmation', [
            'order' => $this->order(),
        ])->layout('layouts.storefront', [
            'title' => 'Order confirmation',
        ]);
    }

    private function order(): Order
    {
        $customer = Auth::guard('customer')->user();
        $sessionOrderId = session('last_order_id');

        abort_if(! $customer instanceof Customer && $sessionOrderId === null, 404);

        return Order::withoutGlobalScopes()
            ->with(['lines', 'payments', 'fulfillments.lines'])
            ->where('store_id', $this->storeId)
            ->whereKey($this->orderId)
            ->where(function ($query) use ($customer, $sessionOrderId): void {
                if ($customer instanceof Customer) {
                    $query->orWhere('customer_id', $customer->getKey());
                }

                if ($sessionOrderId !== null) {
                    $query->orWhere('id', (int) $sessionOrderId);
                }
            })
            ->firstOrFail();
    }
}
