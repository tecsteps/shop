<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
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
        $store = app('current_store');

        $this->validate([
            'name' => ['required', 'max:255'],
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, \Closure $fail) use ($store): void {
                    if ($store instanceof Store && Customer::withoutGlobalScopes()->where('store_id', $store->id)->where('email', $value)->exists()) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'password' => ['required', 'min:8', 'confirmed', Password::defaults()],
        ]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirect('/account');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('layouts::guest');
    }
}
