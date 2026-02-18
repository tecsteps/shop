<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        $orders = $customer->orders()
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
