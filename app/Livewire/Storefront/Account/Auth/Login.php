<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Enums\CartStatus;
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

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'login|'.$this->getIpAddress();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many attempts. Try again in {$seconds} seconds.");

            return;
        }

        $guestCartId = session('cart_id');

        if (Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            $this->mergeGuestCart($guestCartId);

            $this->redirect(session()->pull('url.intended', '/account'));

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'Invalid credentials');
    }

    protected function mergeGuestCart(?int $guestCartId): void
    {
        if (! $guestCartId) {
            return;
        }

        $customer = Auth::guard('customer')->user();
        $store = app('current_store');

        $guestCart = Cart::withoutGlobalScopes()
            ->where('id', $guestCartId)
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->whereNull('customer_id')
            ->first();

        if (! $guestCart) {
            return;
        }

        $customerCart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('status', CartStatus::Active)
            ->first();

        $cartService = app(CartService::class);

        if ($customerCart) {
            $merged = $cartService->mergeOnLogin($guestCart, $customerCart);
            session(['cart_id' => $merged->id]);
        } else {
            $guestCart->update(['customer_id' => $customer->id]);
            session(['cart_id' => $guestCart->id]);
        }
    }

    protected function getIpAddress(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('layouts::guest');
    }
}
