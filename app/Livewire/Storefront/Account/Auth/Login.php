<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(): mixed
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'customer-login:'.request()->ip().':'.strtolower($this->email);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Invalid credentials',
            ]);
        }

        RateLimiter::clear($throttleKey);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended('/account');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.login');
    }
}
