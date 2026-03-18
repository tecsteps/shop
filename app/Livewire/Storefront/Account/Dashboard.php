<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My Account')]
class Dashboard extends Component
{
    #[Computed]
    public function customer(): \App\Models\Customer
    {
        return Auth::guard('customer')->user();
    }

    #[Computed]
    public function recentOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->customer->orders()
            ->latest('placed_at')
            ->limit(5)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.dashboard')
            ->layout('storefront.layouts.app', ['title' => 'My Account']);
    }
}
