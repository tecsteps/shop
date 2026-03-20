<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public string $name = '';

    public bool $marketingOptIn = false;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->name = $customer->name;
        $this->marketingOptIn = (bool) $customer->marketing_opt_in;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'max:255'],
        ]);

        $customer = Auth::guard('customer')->user();
        $customer->update([
            'name' => $this->name,
            'marketing_opt_in' => $this->marketingOptIn,
        ]);
    }

    public function render(): mixed
    {
        $customer = Auth::guard('customer')->user();
        $recentOrders = $customer->orders()
            ->latest()
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ])->layout('layouts.storefront');
    }
}
