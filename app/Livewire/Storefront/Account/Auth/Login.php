<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Enums\CartStatus;
use App\Models\Cart;
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

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many login attempts. Please try again later.']);
        }

        if (! Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $customer = Auth::guard('customer')->user();
        $guestCart = session()->has('cart_id')
            ? Cart::query()->whereKey(session('cart_id'))->where('status', CartStatus::Active)->first()
            : null;

        if ($guestCart) {
            $customerCart = Cart::query()
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($customerCart && $customerCart->id !== $guestCart->id) {
                $customerCart = app(CartService::class)->mergeOnLogin($guestCart, $customerCart);
                session(['cart_id' => $customerCart->id]);
            } else {
                $guestCart->update(['customer_id' => $customer->id]);
            }
        }

        $this->redirectRoute('storefront.account.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('layouts.storefront')
            ->title('Log in - '.app('current_store')->name);
    }
}
