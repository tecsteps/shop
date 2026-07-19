<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Livewire\Storefront\Concerns\MergesGuestCartOnLogin;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Register extends Component
{
    use MergesGuestCartOnLogin;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    /**
     * Authenticated customers are sent straight to their account.
     */
    public function mount(): void
    {
        if (Auth::guard('customer')->check()) {
            $this->redirect('/account');
        }
    }

    /**
     * Create the customer scoped to the current store, auto-login and
     * merge the guest cart (spec 06 §1.2).
     */
    public function register(CustomerService $customers): void
    {
        $store = app('current_store');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('store_id', $store->id),
            ],
            'password' => 'required|min:8|confirmed',
            'marketing_opt_in' => 'boolean',
        ]);

        $customer = $customers->register($store, $validated);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        $this->mergeGuestCartOnLogin($customer);

        $this->redirect('/account');
    }

    /**
     * Render the registration page in the storefront layout (spec 04 §10.2).
     */
    public function render(): View
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('storefront.layouts.app')
            ->title('Create an account');
    }
}
