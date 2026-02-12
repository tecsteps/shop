<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Create Account')]
class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function register(): void
    {
        $this->validate();

        $store = app('current_store');

        $existingCustomer = Customer::where('store_id', $store->id)
            ->where('email', $this->email)
            ->first();

        if ($existingCustomer) {
            $this->addError('email', 'An account with this email already exists.');

            return;
        }

        $customer = Customer::create([
            'store_id' => $store->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirect(route('storefront.account.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register');
    }
}
