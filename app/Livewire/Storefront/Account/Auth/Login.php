<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class Login extends StorefrontComponent
{
    public string $email = '';

    public string $password = '';

    public function mount(): mixed
    {
        if (Auth::guard('customer')->check()) {
            return $this->redirect(url('/account'), navigate: true);
        }

        return null;
    }

    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $key = 'customer-login:'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many attempts. Try again in {$seconds} seconds.");

            return null;
        }

        if (! Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($key, 60);
            $this->addError('credentials', 'Invalid credentials');

            return null;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();
        $this->mergeGuestCart();

        return $this->redirectIntended(default: url('/account'), navigate: true);
    }

    public function render(): View
    {
        return $this->storefront(view('storefront.account.auth.login'), 'Log in - '.$this->currentStore()->name);
    }

    private function mergeGuestCart(): void
    {
        $guestCartId = session('cart_id');
        if (! $guestCartId) {
            return;
        }

        $guestCart = Cart::query()->where('store_id', $this->currentStore()->getKey())->whereKey($guestCartId)->whereNull('customer_id')->first();
        if (! $guestCart) {
            return;
        }

        $service = app(CartService::class);
        $customer = Auth::guard('customer')->user();
        $customerCart = Cart::query()->where('store_id', $this->currentStore()->getKey())->where('customer_id', $customer->getAuthIdentifier())->where('status', 'active')->first()
            ?: $service->create($this->currentStore(), $customer);
        $service->mergeOnLogin($guestCart, $customerCart);
        session(['cart_id' => $customerCart->getKey()]);
    }
}
