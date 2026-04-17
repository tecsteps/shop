<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Register extends Component
{
    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    #[Validate('required|string|min:8')]
    public string $passwordConfirmation = '';

    public bool $marketingOptIn = false;

    public function register(): mixed
    {
        $this->validate();

        $storeId = app('current_store')->id;

        if (Customer::where('store_id', $storeId)->where('email', $this->email)->exists()) {
            $this->addError('email', 'An account with this email already exists.');

            return null;
        }

        $customer = Customer::create([
            'store_id' => $storeId,
            'email' => $this->email,
            'password_hash' => $this->password,
            'name' => $this->name,
            'marketing_opt_in' => $this->marketingOptIn,
            'email_verified_at' => now(),
        ]);

        Auth::guard('customer')->login($customer);
        request()->session()->regenerate();

        return $this->redirect(route('storefront.account.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register')->title('Create account');
    }
}
