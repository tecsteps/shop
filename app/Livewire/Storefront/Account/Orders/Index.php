<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.storefront.account.orders.index', [
            'orders' => Auth::guard('customer')->user()->orders()->latest('placed_at')->paginate(10),
        ])->layout('layouts.storefront', ['title' => 'Orders - '.app('current_store')->name]);
    }
}
