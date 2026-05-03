<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $marketingOptIn = false;

    public function register(): mixed
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'same:password'],
            'marketingOptIn' => ['bool'],
        ]);

        $store = app('current_store');
        $email = Str::lower($this->email);
        $customer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', $email)
            ->first();

        if ($customer !== null && $customer->password_hash !== null) {
            throw ValidationException::withMessages([
                'email' => ['An account already exists for this email.'],
            ]);
        }

        $customer ??= new Customer([
            'store_id' => $store->id,
            'email' => $email,
        ]);

        $customer->forceFill([
            'name' => $this->name,
            'password' => $this->password,
            'marketing_opt_in' => $this->marketingOptIn,
        ])->save();

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        return $this->redirect(route('storefront.account.dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('storefront.layouts.app', [
                'title' => 'Create account',
            ]);
    }
}
