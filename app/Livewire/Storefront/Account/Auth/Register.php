<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class Register extends StorefrontComponent
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $marketingOptIn = false;

    public function mount(): mixed
    {
        return Auth::guard('customer')->check() ? $this->redirect(url('/account'), navigate: true) : null;
    }

    public function register(): mixed
    {
        $store = $this->currentStore();
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('customers', 'email')
                ->where('store_id', $store->getKey())
                ->whereNotNull('password_hash')],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
            'marketingOptIn' => ['boolean'],
        ], ['password.same' => 'The passwords do not match.']);

        $customer = Customer::withoutGlobalScopes()->firstOrNew([
            'store_id' => $store->getKey(),
            'email' => mb_strtolower($this->email),
        ]);
        $customer->fill([
            'name' => $this->name,
            'password_hash' => Hash::make($this->password),
            'marketing_opt_in' => $this->marketingOptIn,
        ])->save();

        Auth::guard('customer')->login($customer);
        request()->session()->regenerate();

        if ($cartId = session('cart_id')) {
            Cart::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->whereKey($cartId)
                ->whereNull('customer_id')
                ->where('status', 'active')
                ->update(['customer_id' => $customer->getKey()]);
        }

        $this->dispatch('toast', type: 'success', message: 'Account created successfully');

        return $this->redirect(url('/account'), navigate: true);
    }

    public function render(): View
    {
        return $this->storefront(view('storefront.account.auth.register'), 'Create an account - '.$this->currentStore()->name);
    }
}
