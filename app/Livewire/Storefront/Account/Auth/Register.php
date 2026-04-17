<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function register(): mixed
    {
        $store = app('current_store');
        $storeId = $store ? $store->getKey() : null;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where(fn ($q) => $q->where('store_id', $storeId)),
            ],
            'password' => 'required|string|min:8|confirmed',
            'marketing_opt_in' => 'boolean',
        ]);

        $customer = new Customer;
        $customer->store_id = $storeId;
        $customer->email = $this->email;
        $customer->password_hash = Hash::make($this->password);
        $customer->name = $this->name;
        $customer->marketing_opt_in = $this->marketing_opt_in ? 1 : 0;
        $customer->save();

        if (! Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
        )) {
            throw ValidationException::withMessages(['email' => 'Could not sign in after registration.']);
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect('/account');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register');
    }
}
