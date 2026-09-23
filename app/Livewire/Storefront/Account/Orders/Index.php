<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        return view('storefront.account.orders.index', [
            'orders' => Order::query()->where('customer_id', auth('customer')->id())->latest('placed_at')->paginate(10),
        ])->layout('storefront.layouts.app');
    }
}
