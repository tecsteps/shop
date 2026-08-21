<?php

namespace App\Livewire\Storefront\Account\Orders;

use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.storefront.account.orders.index', ['orders' => auth('customer')->user()->orders()->latest()->get()])->layout('layouts.storefront');
    }
}
