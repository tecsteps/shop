<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public string $customerName = '';

    public string $customerEmail = '';

    /** @var Collection<int, \App\Models\Order> */
    public Collection $recentOrders;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->customerName = $customer->name ?? '';
        $this->customerEmail = $customer->email;
        $this->recentOrders = $customer->orders()
            ->latest('placed_at')
            ->limit(5)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.dashboard')
            ->layout('layouts.storefront');
    }
}
