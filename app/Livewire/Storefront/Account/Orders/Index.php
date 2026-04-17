<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('store_id', app('current_store')->id)
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ])->layout('storefront.layouts.app', [
            'title' => 'Orders',
        ]);
    }
}
