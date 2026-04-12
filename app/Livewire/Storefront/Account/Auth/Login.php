<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Login extends Component
{
    use EnsuresStore;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        $this->ensureCurrentStore();

        if (Auth::guard('customer')->check()) {
            $this->redirect(route('storefront.account.dashboard'), navigate: false);
        }
    }

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $store = $this->ensureCurrentStore();

        $customer = Customer::query()
            ->where('store_id', $store->id)
            ->where('email', $this->email)
            ->first();

        if ($customer === null || ! Hash::check($this->password, (string) $customer->password_hash)) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        Auth::guard('customer')->login($customer, $this->remember);
        session()->regenerate();

        $this->redirect(route('storefront.account.dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.login');
    }
}
