<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Dashboard extends Component
{
    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $customer->orders()->latest('placed_at')->limit(5)->get(),
        ]);
    }
}
