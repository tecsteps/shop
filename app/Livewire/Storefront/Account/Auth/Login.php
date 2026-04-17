<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $throttleKey = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('email', "Too many attempts. Try again in {$seconds} seconds.");

            return;
        }

        if (! Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::hit($throttleKey);
            $this->addError('email', 'Invalid credentials.');

            return;
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirect(route('storefront.account.dashboard'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.account.auth.login');
    }
}
