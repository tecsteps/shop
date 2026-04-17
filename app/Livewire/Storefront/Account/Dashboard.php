<?php

namespace App\Livewire\Storefront\Account;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Dashboard extends Component
{
    use EnsuresStore;

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    public function render(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $recentOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ]);
    }
}
