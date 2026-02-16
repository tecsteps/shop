<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.account.orders.index', [
            'orders' => $customer->orders()->latest('placed_at')->paginate(10),
        ]);
    }
}
