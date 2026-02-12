<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Log in')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $throttleKey = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('email', 'Too many login attempts. Please try again later.');

            return;
        }

        if (Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            $customer = Auth::guard('customer')->user();
            $customer->update(['last_login_at' => now()]);

            // Merge guest cart if exists
            $guestCartId = session('cart_id');

            if ($guestCartId) {
                $guestCart = Cart::find($guestCartId);

                if ($guestCart && $guestCart->lines()->count() > 0) {
                    $cartService = app(CartService::class);
                    $mergedCart = $cartService->mergeOnLogin($guestCart, $customer);
                    session(['cart_id' => $mergedCart->id]);
                }
            }

            $this->redirect(route('storefront.account.dashboard'), navigate: true);

            return;
        }

        RateLimiter::hit($throttleKey, 60);
        $this->addError('email', 'These credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.login');
    }
}
