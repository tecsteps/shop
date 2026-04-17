<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Dashboard extends Component
{
    public function logout(): void
    {
        /** @var \Illuminate\Auth\SessionGuard $guard */
        $guard = Auth::guard('customer');
        $guard->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/account/login');
    }

    public function render(): View
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();

        $recentOrders = $customer->orders()
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ]);
    }
}
