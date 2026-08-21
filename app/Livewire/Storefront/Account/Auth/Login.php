<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(CartService $carts): void
    {
        $credentials = $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'customer-login|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Try again later.');

            return;
        }

        RateLimiter::hit($key, 60);

        if (! Auth::guard('customer')->attempt([...$credentials, 'status' => 'active'], $this->remember)) {
            $this->addError('email', 'Invalid credentials');

            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();
        $customer = Auth::guard('customer')->user();
        $store = app('current_store');
        $guestCartId = session('cart_id_'.$store->getKey(), session('cart_id'));
        $guestCart = $guestCartId === null ? null : Cart::withoutGlobalScopes()->whereKey($guestCartId)->where('store_id', $store->getKey())->whereNull('customer_id')->where('status', 'active')->first();
        $customerCart = $customer->carts()->where('status', 'active')->latest()->first() ?? $carts->create($store, $customer);

        if ($guestCart !== null && $guestCart->getKey() !== $customerCart->getKey()) {
            $carts->mergeOnLogin($guestCart, $customerCart);
        } else {
            session(['cart_id_'.$store->getKey() => $customerCart->getKey(), 'cart_id' => $customerCart->getKey()]);
        }

        $this->redirect(route('account.dashboard'));
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.login')->layout('layouts.storefront');
    }
}
