<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function logout(): mixed
    {
        Auth::guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect(route('storefront.account.login'), navigate: true);
    }

    public function render(): View
    {
        $customer = $this->customer();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => Order::query()
                ->where('customer_id', $customer->id)
                ->latest('placed_at')
                ->limit(5)
                ->get(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Account',
        ]);
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
