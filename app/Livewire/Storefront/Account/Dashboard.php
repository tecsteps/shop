<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        $customer = Auth::guard('customer')->user();

        $recentOrders = Order::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('store_id', app('current_store')->id)
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ])->layout('storefront.layouts.app', [
            'title' => 'My Account',
        ]);
    }
}
