<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    use ResolvesStore;

    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render()
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        $candidates = [$this->orderNumber, '#'.ltrim($this->orderNumber, '#')];

        $order = $customer->orders()
            ->where('store_id', $store->getKey())
            ->whereIn('order_number', $candidates)
            ->with(['orderLines', 'fulfillments.fulfillmentLines', 'payments'])
            ->first();

        abort_if($order === null, 404);

        return view('livewire.storefront.account.orders.show', [
            'store' => $store,
            'order' => $order,
        ])->layout('storefront.layouts.app', [
            'title' => 'Order '.$order->order_number.' - '.$store->name,
            'metaDescription' => 'Order '.$order->order_number.' at '.$store->name,
        ]);
    }
}
