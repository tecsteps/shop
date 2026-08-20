<?php

namespace App\Livewire\Storefront\Account;

use Livewire\Component;

class Dashboard extends Component
{
    public function render(): mixed
    {
        $customer = auth('customer')->user()->loadCount('orders');

        return view('livewire.storefront.account.dashboard', compact('customer'))->layout('layouts.storefront');
    }
}
