<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Dashboard extends Component
{
    public string $name = '';

    public bool $marketingOptIn = false;

    public function mount(): void
    {
        $customer = $this->customer();

        $this->name = (string) $customer->name;
        $this->marketingOptIn = (bool) $customer->marketing_opt_in;
    }

    /**
     * Update the customer's profile (name and marketing preference).
     */
    public function updateProfile(): void
    {
        $this->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'marketingOptIn' => ['boolean'],
            ],
            [],
            ['name' => __('name')],
        );

        $this->customer()->update([
            'name' => $this->name,
            'marketing_opt_in' => $this->marketingOptIn,
        ]);

        session()->flash('profile-updated', __('Your profile has been updated.'));
    }

    public function render(): View
    {
        $recentOrders = $this->customer()
            ->orders()
            ->latest('placed_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.storefront.account.dashboard', [
            'customer' => $this->customer(),
            'recentOrders' => $recentOrders,
        ])->title(__('My account'));
    }

    protected function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer;
    }
}
