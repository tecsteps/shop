<?php

namespace App\Livewire\Admin\Auth;

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

        if (Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            $user = Auth::guard('web')->user();
            $user->update(['last_login_at' => now()]);

            $this->redirect('/admin');

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'Invalid credentials');
    }

    protected function getIpAddress(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login')
            ->layout('layouts::guest');
    }
}
