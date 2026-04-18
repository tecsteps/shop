<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Dashboard extends Component
{
    public function render()
    {
        $customer = auth('customer')->user();

        $recentOrders = $customer
            ? Order::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('placed_at')
                ->limit(5)
                ->get()
            : new Collection;

        return view('livewire.storefront.account.dashboard', [
            'recentOrders' => $recentOrders,
        ]);
    }
}
