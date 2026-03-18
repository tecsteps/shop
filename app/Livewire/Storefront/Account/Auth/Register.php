<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'marketing_opt_in' => ['boolean'],
        ]);

        $store = app('current_store');

        $existingCustomer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', $validated['email'])
            ->exists();

        if ($existingCustomer) {
            $this->addError('email', __('The email has already been taken.'));

            return;
        }

        $customer = Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'marketing_opt_in' => $validated['marketing_opt_in'],
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        $this->redirect(route('storefront.account'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('layouts.auth', ['title' => 'Register']);
    }
}
