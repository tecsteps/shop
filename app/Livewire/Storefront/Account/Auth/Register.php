<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketingOptIn = false;

    public function register(): void
    {
        $store = app('current_store');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    if (Customer::query()->where('store_id', app('current_store')->id)->where('email', $value)->exists()) {
                        $fail('An account with this email already exists.');
                    }
                },
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ], [], ['password' => 'password']);

        $customer = Customer::query()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => $validated['password'],
            'marketing_opt_in' => $this->marketingOptIn,
        ]);

        if ($guestCartId = session('cart_id')) {
            Cart::query()
                ->whereKey($guestCartId)
                ->where('status', CartStatus::Active)
                ->update(['customer_id' => $customer->id]);
        }

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirectRoute('storefront.account.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('layouts.storefront')
            ->title('Create account - '.app('current_store')->name);
    }
}
