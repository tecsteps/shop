<?php

namespace App\Livewire\Storefront\Account\Auth;

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

        $key = 'login:'.$this->getIpAddress();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            abort(429, "Too many attempts. Try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, 60);

        if (! Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            $this->addError('email', __('Invalid credentials'));

            return;
        }

        RateLimiter::clear($key);

        session()->regenerate();

        $this->redirect(route('storefront.account'), navigate: true);
    }

    protected function getIpAddress(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('layouts.auth', ['title' => 'Login']);
    }
}
