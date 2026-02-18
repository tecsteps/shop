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

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->validate();

        $throttleKey = 'login|'.$this->getRequestIp();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many attempts. Try again in {$seconds} seconds.");

            return;
        }

        if (Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            $this->redirect(session()->pull('url.intended', '/account'));

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'Invalid credentials');
    }

    protected function getRequestIp(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.account.auth.login');
    }
}
