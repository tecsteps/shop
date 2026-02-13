<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        $customer = Auth::guard('customer')->user();
        $orders = $customer->orders()->latest()->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
