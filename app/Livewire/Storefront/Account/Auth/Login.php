<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to authenticate the customer against the current store.
     *
     * The customer guard uses the CustomerUserProvider, which injects the
     * current store's id into the credential query, so this never crosses store
     * boundaries.
     */
    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        return $this->redirectIntended(default: route('account.dashboard'), navigate: true);
    }

    /**
     * Ensure the login request is not rate limited (5/min per email+IP).
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
        ]);
    }

    /**
     * The per-email-and-IP throttle key for customer login attempts.
     */
    protected function throttleKey(): string
    {
        return 'customer|'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.login');
    }
}
