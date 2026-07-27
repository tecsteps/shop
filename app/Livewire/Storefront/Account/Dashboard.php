<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public string $name = '';

    public bool $marketing_opt_in = false;

    public bool $profileSaved = false;

    /**
     * Prefill the profile form from the authenticated customer.
     */
    public function mount(): void
    {
        $customer = $this->customer();

        $this->name = (string) ($customer->name ?? '');
        $this->marketing_opt_in = (bool) $customer->marketing_opt_in;
    }

    /**
     * Persist the editable profile fields (name, marketing preference).
     */
    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'marketing_opt_in' => 'boolean',
        ]);

        $this->customer()->update($validated);

        $this->profileSaved = true;
    }

    /**
     * Render the account dashboard with the five most recent orders
     * (spec 04 §10.3).
     */
    public function render(): View
    {
        $customer = $this->customer();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $customer->orders()->latest('placed_at')->limit(5)->get(),
        ])
            ->layout('storefront.layouts.app')
            ->title('My account');
    }

    /**
     * The authenticated storefront customer.
     */
    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
