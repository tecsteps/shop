<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();
        $store = app()->bound('current_store') ? app('current_store') : null;

        $order = null;
        if ($store && $customer) {
            $order = Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('order_number', $this->orderNumber)
                ->with(['lines', 'fulfillments', 'payments'])
                ->first();
        }

        if (! $order) {
            abort(404);
        }

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ]);
    }
}
