<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Register extends Component
{
    use EnsuresStore;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    public function mount(): void
    {
        $this->ensureCurrentStore();

        if (Auth::guard('customer')->check()) {
            $this->redirect(route('storefront.account.dashboard'), navigate: false);
        }
    }

    public function register(): void
    {
        $store = $this->ensureCurrentStore();

        $this->validate([
            'name' => 'required|string|max:120',
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')->where(fn ($query) => $query->where('store_id', $store->id)),
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var Customer $customer */
        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => $this->name,
            'email' => $this->email,
            'password_hash' => $this->password,
            'marketing_opt_in' => $this->marketing_opt_in,
        ]);

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirect(route('storefront.account.dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.register');
    }
}
