<?php

namespace App\Livewire\Storefront\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Dashboard extends Component
{
    public string $name = '';

    public bool $marketingOptIn = false;

    public function mount(): void
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();
        $this->name = $customer->name ?? '';
        $this->marketingOptIn = (bool) $customer->marketing_opt_in;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();
        $customer->update([
            'name' => $this->name,
            'marketing_opt_in' => $this->marketingOptIn,
        ]);

        session()->flash('message', 'Profile updated successfully.');
    }

    public function render(): View
    {
        /** @var \App\Models\Customer $customer */
        $customer = Auth::guard('customer')->user();
        $recentOrders = $customer->orders()->latest()->limit(5)->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
        ]);
    }
}
