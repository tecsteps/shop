<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $store = app('current_store');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $existingCustomer = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingCustomer) {
            $this->addError('email', 'This email is already registered.');

            return;
        }

        $customer = Customer::query()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        $this->redirect(route('storefront.account.dashboard'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.account.auth.register');
    }
}
