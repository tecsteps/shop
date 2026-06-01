<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Customer account dashboard: a welcome, quick links, and recent orders.
 */
#[Layout('storefront.layouts.app')]
#[Title('Account')]
class Dashboard extends Component
{
    public function render()
    {
        $customer = Auth::guard('customer')->user();

        $recentOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ]);
    }
}
