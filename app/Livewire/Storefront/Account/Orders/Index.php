<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        $orders = $customer->orders()
            ->withoutGlobalScopes()
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ])->layout('layouts.storefront.app', [
            'title' => 'Order History',
        ]);
    }
}
