<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(CartService $carts): void
    {
        $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Please try again later.']);
        }

        $guestCart = session('cart_id') ? Cart::query()->find(session('cart_id')) : null;
        $authenticated = Auth::guard('customer')->attempt([
            'email' => $this->email,
            'password' => $this->password,
            'store_id' => app('current_store')->id,
        ], $this->remember);

        if (! $authenticated) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        session()->regenerate();
        RateLimiter::clear($key);
        $customer = Auth::guard('customer')->user();

        if ($guestCart && $guestCart->customer_id === null) {
            $customerCart = $customer->carts()->where('status', CartStatus::Active)->latest()->first() ?? $carts->create(app('current_store'), $customer);
            $carts->mergeOnLogin($guestCart->load('lines'), $customerCart);
        }

        $this->redirectIntended(route('storefront.account.dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('layouts.storefront', ['title' => 'Sign in - '.app('current_store')->name]);
    }
}
