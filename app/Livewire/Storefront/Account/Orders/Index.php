<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $orders = Auth::guard('customer')->user()
            ->orders()
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', ['orders' => $orders])
            ->layout('layouts.storefront')
            ->title('Order History - '.app('current_store')->name);
    }
}
