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

    public bool $marketing_opt_in = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $storeId = app('current_store')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                "unique:customers,email,NULL,id,store_id,{$storeId}",
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'marketing_opt_in' => ['boolean'],
        ];
    }

    public function register(): void
    {
        $validated = $this->validate();

        $customer = Customer::create([
            'store_id' => app('current_store')->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        $this->redirect('/account');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.account.auth.register');
    }
}
