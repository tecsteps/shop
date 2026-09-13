<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesStore, WithPagination;

    public function render()
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        $orders = $customer->orders()->latest('placed_at')->latest('id')->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'store' => $store,
            'orders' => $orders,
        ])->layout('storefront.layouts.app', [
            'title' => 'Order History - '.$store->name,
            'metaDescription' => 'Your order history at '.$store->name,
        ]);
    }
}
