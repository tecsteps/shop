<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

        $key = 'customer_login:'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Try again in a minute.');

            return null;
        }

        if (! Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('email', 'Invalid credentials.');

            return null;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        return $this->redirect(route('storefront.account.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.login')->title('Sign in');
    }
}
