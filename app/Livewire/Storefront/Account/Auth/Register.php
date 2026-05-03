<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    public int $storeId;

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
    }

    public function register(): void
    {
        $store = Store::query()->findOrFail($this->storeId);

        app()->instance('current_store', $store);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('store_id', $store->getKey()),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'marketing_opt_in' => ['boolean'],
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'marketing_opt_in' => $validated['marketing_opt_in'],
        ]);

        Auth::guard('customer')->login($customer);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->redirectRoute('account.dashboard', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('layouts.auth');
    }
}
