<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $key = 'admin-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        if (! Auth::guard('web')->attempt([
            'email' => $this->email,
            'password' => $this->password,
            'status' => 'active',
        ], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        RateLimiter::clear($key);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        Auth::user()?->forceFill(['last_login_at' => now()])->save();

        $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login')
            ->layout('layouts.auth');
    }
}
