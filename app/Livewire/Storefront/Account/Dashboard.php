<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
#[Title('My Account')]
class Dashboard extends Component
{
    public function logout(): void
    {
        Auth::guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/');
    }

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();

        $recentOrders = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $customer->store_id)
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
