<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $customer = Auth::guard('customer')->user();

        $recentOrders = $customer->orders()->latest('placed_at')->limit(5)->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ])
            ->layout('layouts.storefront')
            ->title('My Account - '.app('current_store')->name);
    }
}
