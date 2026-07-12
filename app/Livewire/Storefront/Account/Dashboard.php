<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class Dashboard extends CustomerComponent
{
    public function mount(): void
    {
        $this->authenticatedCustomer();
    }

    public function logout(): mixed
    {
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(url('/account/login'), navigate: true);
    }

    public function render(): View
    {
        $customer = $this->authenticatedCustomer();
        $orders = Order::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->where('customer_id', $customer->getAuthIdentifier())
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return $this->storefront(view('storefront.account.dashboard', compact('customer', 'orders')), 'Your account - '.$this->currentStore()->name);
    }
}
