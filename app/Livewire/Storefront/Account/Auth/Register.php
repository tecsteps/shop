<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): mixed
    {
        $store = app('current_store');
        $this->resetErrorBag();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')->where('store_id', $store->id),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'state' => 'active',
            'email_verified_at' => now(),
        ]);

        Auth::guard('customer')->login($customer);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->route('account.dashboard');
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register');
    }
}
