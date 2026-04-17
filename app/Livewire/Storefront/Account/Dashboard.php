<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function logout(): mixed
    {
        Auth::guard('customer')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('customer.login');
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();

        $recentOrders = $customer->orders()
            ->withoutGlobalScopes()
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ])->layout('layouts.storefront.app', [
            'title' => 'My Account',
        ]);
    }
}
