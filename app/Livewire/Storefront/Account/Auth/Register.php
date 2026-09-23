<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketingOptIn = false;

    public function register(): mixed
    {
        $key = 'customer-register:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many registration attempts. Try again in a minute.']);
        }

        RateLimiter::hit($key, 60);
        $store = app('current_store');
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('customers', 'email')->where('store_id', $store->id)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'marketingOptIn' => ['boolean'],
        ]);
        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'marketing_opt_in' => $data['marketingOptIn'],
        ]);

        RateLimiter::clear($key);
        Auth::guard('customer')->login($customer);
        session()->regenerate();

        return $this->redirectRoute('storefront.account.dashboard');
    }

    public function render(): mixed
    {
        return view('storefront.account.register')->layout('storefront.layouts.app');
    }
}
