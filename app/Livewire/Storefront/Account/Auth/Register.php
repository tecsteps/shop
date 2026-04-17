<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
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

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $storeId = app()->bound('current_store') ? app('current_store')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                "unique:customers,email,NULL,id,store_id,{$storeId}",
            ],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'marketing_opt_in' => ['boolean'],
        ];
    }

    public function register(): mixed
    {
        $this->validate();

        $store = app('current_store');

        $customer = Customer::query()->create([
            'store_id' => $store->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        return redirect('/account');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register');
    }
}
