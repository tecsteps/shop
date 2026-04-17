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

    /** @var array<string, list<string>> */
    protected array $rules = [
        'email' => ['required', 'email'],
        'password' => ['required'],
    ];

    public function login(): void
    {
        $this->validate();

        $throttleKey = 'admin-login:'.request()->ip();

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

            $this->redirect(route('admin.dashboard'), navigate: true);

            return;
        }

        RateLimiter::hit($throttleKey);
        $this->addError('email', 'Invalid credentials.');
    }

    public function render()
    {
        return view('livewire.admin.auth.login')
            ->layout('layouts.admin-auth');
    }
}
