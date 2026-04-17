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

    public bool $marketingOptIn = false;

    public function register(): void
    {
        $store = app('current_store');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($store) {
                    if ($store && Customer::query()
                        ->where('store_id', $store->id)
                        ->where('email', $value)
                        ->exists()
                    ) {
                        $fail('An account with this email already exists.');
                    }
                },
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => $this->name,
            'email' => $this->email,
            'password_hash' => Hash::make($this->password),
            'marketing_opt_in' => $this->marketingOptIn,
        ]);

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirect(route('customer.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('layouts.storefront');
    }
}
