<?php

namespace App\Livewire\Storefront\Account;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Order;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Dashboard extends Component
{
    use InteractsWithStore;

    #[Computed]
    public function customer(): \App\Models\Customer
    {
        return auth()->guard('customer')->user();
    }

    #[Computed]
    public function recentOrders(): SupportCollection
    {
        return Order::where('customer_id', $this->customer->id)
            ->orderByDesc('placed_at')
            ->limit(5)
            ->get();
    }
}
