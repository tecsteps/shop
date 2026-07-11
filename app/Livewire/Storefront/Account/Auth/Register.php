<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Services\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    public function register(CustomerService $customers): void
    {
        $customer = $customers->register(app('current_store'), [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);
        Auth::guard('customer')->login($customer);
        session()->regenerate();
        $this->redirectRoute('storefront.account.dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('layouts.storefront', ['title' => 'Create account - '.app('current_store')->name]);
    }
}
