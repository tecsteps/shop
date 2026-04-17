<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();
        $store = app()->bound('current_store') ? app('current_store') : null;

        $orders = null;
        if ($store && $customer) {
            $orders = Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->orderByDesc('placed_at')
                ->paginate(10);
        }

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
