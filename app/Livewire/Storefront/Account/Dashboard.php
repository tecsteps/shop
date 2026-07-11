<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function logout(): void
    {
        Auth::guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirectRoute('storefront.account.login', navigate: true);
    }

    public function render(): View
    {
        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'orders' => $customer->orders()->latest('placed_at')->limit(5)->get(),
        ])->layout('layouts.storefront', ['title' => 'Your account - '.app('current_store')->name]);
    }
}
