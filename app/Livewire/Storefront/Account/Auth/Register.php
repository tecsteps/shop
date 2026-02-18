<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use App\Models\Store;
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
        /** @var Store $store */
        $store = app('current_store');
        $storeId = $store->id;

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
        /** @var array{name: string, email: string, password: string, marketing_opt_in: bool} $validated */
        $validated = $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $customer = Customer::query()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);

        /** @var \Illuminate\Auth\SessionGuard $guard */
        $guard = Auth::guard('customer');
        $guard->login($customer);

        session()->regenerate();

        $this->redirect('/account');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.account.auth.register');
    }
}
