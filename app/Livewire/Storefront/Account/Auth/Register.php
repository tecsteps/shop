<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Register extends Component
{
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function register(): void
    {
        $data = $this->validate(['firstName' => ['required', 'string', 'max:255'], 'lastName' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('customers', 'email')->where('store_id', app('current_store')->getKey())], 'password' => ['required', 'min:8', 'same:passwordConfirmation']]);
        $customer = Customer::create(['store_id' => app('current_store')->getKey(), 'first_name' => $data['firstName'], 'last_name' => $data['lastName'], 'email' => $data['email'], 'password_hash' => $data['password'], 'status' => 'active']);
        Auth::guard('customer')->login($customer);
        $this->redirect(route('account.dashboard'));
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register')->layout('layouts.storefront');
    }
}
