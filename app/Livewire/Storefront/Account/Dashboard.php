<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $recentOrders = Order::query()
            ->where('customer_id', $customer->getKey())
            ->orderByDesc('placed_at')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders instanceof Collection ? $recentOrders : collect($recentOrders),
        ]);
    }
}
