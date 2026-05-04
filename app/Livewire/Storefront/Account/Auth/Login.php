<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public int $storeId;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $key = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        app()->instance('current_store', Store::query()->findOrFail($this->storeId));

        if (! Auth::guard('customer')->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        RateLimiter::clear($key);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $customer = Auth::guard('customer')->user();

        if ($customer instanceof Customer && session()->has('cart_id')) {
            app(CartService::class)->getOrCreateForSession(Store::query()->findOrFail($this->storeId), $customer);
        }

        $this->redirectRoute('account.dashboard', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('layouts.auth');
    }
}
