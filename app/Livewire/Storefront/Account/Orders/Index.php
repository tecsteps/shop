<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Account\CustomerComponent;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\WithPagination;

class Index extends CustomerComponent
{
    use WithPagination;

    public function mount(): void
    {
        $this->authenticatedCustomer();
    }

    public function render(): View
    {
        $customer = $this->authenticatedCustomer();
        $orders = Order::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->where('customer_id', $customer->getAuthIdentifier())
            ->latest('placed_at')
            ->paginate(10);

        return $this->storefront(view('storefront.account.orders.index', compact('orders')), 'Order history - '.$this->currentStore()->name);
    }
}
