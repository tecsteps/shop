<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $store = app('current_store');

        $existingCustomer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', $this->email)
            ->exists();

        if ($existingCustomer) {
            $this->addError('email', 'An account with this email already exists.');

            return;
        }

        $customer = Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => $this->password,
        ]);

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirect('/');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register');
    }
}
