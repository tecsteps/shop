<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Create Account')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $storeId = app('current_store')->id;

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')->where('store_id', $storeId),
            ],
            'password' => 'required|string|min:8|confirmed',
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function register(): void
    {
        $validated = $this->validate();

        $customer = Customer::create([
            'store_id' => app('current_store')->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'marketing_opt_in' => $validated['marketing_opt_in'] ?? false,
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        $this->redirect('/account');
    }
}
