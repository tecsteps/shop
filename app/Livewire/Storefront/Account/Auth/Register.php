<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var \App\Models\Store|null $store */
        $store = app()->bound('current_store') ? app('current_store') : null;
        $storeId = $store?->id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('store_id', $storeId),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function register(): void
    {
        $this->validate();

        /** @var \App\Models\Store|null $store */
        $store = app()->bound('current_store') ? app('current_store') : null;
        $storeId = $store?->id;

        $customer = Customer::withoutGlobalScopes()->create([
            'store_id' => $storeId,
            'name' => trim($this->first_name.' '.$this->last_name),
            'email' => $this->email,
            'password_hash' => Hash::make($this->password),
        ]);

        Auth::guard('customer')->login($customer);

        session()->regenerate();

        $this->redirect('/account');
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.register');
    }
}
