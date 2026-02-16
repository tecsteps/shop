<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'customer-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many login attempts. Please try again in {$seconds} seconds.");

            return;
        }

        if (! Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey);
            $this->addError('email', 'Invalid credentials.');

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $this->redirect('/');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.login');
    }
}
