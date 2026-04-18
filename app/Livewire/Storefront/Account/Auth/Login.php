<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): mixed
    {
        $this->validate();

        $key = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Please try again in a minute.'),
            ]);
        }

        if (! Auth::guard('customer')->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        RateLimiter::clear($key);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->mergeGuestCart();

        return redirect()->intended(route('account.dashboard'));
    }

    protected function mergeGuestCart(): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return;
        }

        $store = app('current_store');
        $cartService = app(CartService::class);
        $session = session();
        $guestCartId = $session->get(CartService::SESSION_KEY);

        $guest = $guestCartId
            ? Cart::query()->where('store_id', $store->id)->find($guestCartId)
            : null;

        $customerCart = $cartService->getOrCreateForSession($store, $customer);

        if ($guest && $guest->id !== $customerCart->id) {
            $cartService->mergeOnLogin($guest, $customerCart);
            $session->put(CartService::SESSION_KEY, $customerCart->id);
        }
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.login');
    }
}
