<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    /**
     * Register a new customer scoped to the current store and log them in.
     */
    public function register()
    {
        $storeId = app('current_store')->id;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where(fn ($query) => $query->where('store_id', $storeId)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'marketing_opt_in' => ['boolean'],
        ]);

        $customer = Customer::create([
            'store_id' => $storeId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        return $this->redirect(route('account.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register');
    }
}
