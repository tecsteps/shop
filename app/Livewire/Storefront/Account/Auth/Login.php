<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public string $redirectTo = '';

    public function mount(): void
    {
        $redirect = request()->query('redirect');
        $this->redirectTo = is_string($redirect) ? ($this->safeRedirectPath($redirect) ?? '') : '';
    }

    public function login(CartService $carts): mixed
    {
        $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many login attempts. Try again in a minute.']);
        }

        RateLimiter::hit($key, 60);
        $store = app('current_store');

        if (! Auth::guard('customer')->attempt(['store_id' => $store->id, 'email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();
        $customer = Auth::guard('customer')->user();
        $guestCartId = session('cart_id');

        if ($guestCartId) {
            $guestCart = \App\Models\Cart::withoutGlobalScopes()->where('store_id', $store->id)->whereNull('customer_id')->find($guestCartId);

            if ($guestCart) {
                $carts->mergeGuestCart($guestCart, $customer);
            }
        }

        $redirectTo = $this->safeRedirectPath($this->redirectTo);

        return $redirectTo !== null ? $this->redirect($redirectTo) : $this->redirectRoute('storefront.account.dashboard');
    }

    private function safeRedirectPath(string $redirect): ?string
    {
        if ($redirect === '' || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//') || str_contains($redirect, '\\') || preg_match('/[\x00-\x1F\x7F]/', $redirect)) {
            return null;
        }

        $parsedRedirect = parse_url($redirect);

        if ($parsedRedirect === false || isset($parsedRedirect['scheme']) || isset($parsedRedirect['host']) || isset($parsedRedirect['user']) || isset($parsedRedirect['pass'])) {
            return null;
        }

        return $redirect;
    }

    public function render(): mixed
    {
        return view('storefront.account.login')->layout('storefront.layouts.app');
    }
}
