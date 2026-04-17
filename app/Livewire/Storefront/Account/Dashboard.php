<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Dashboard extends Component
{
    public function logout(): void
    {
        Auth::guard('customer')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('storefront.account.login'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();
        $store = app()->bound('current_store') ? app('current_store') : null;

        $recentOrders = collect();
        if ($store && $customer) {
            $recentOrders = Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->orderByDesc('placed_at')
                ->limit(5)
                ->get();
        }

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ]);
    }
}
