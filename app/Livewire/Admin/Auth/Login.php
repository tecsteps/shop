<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
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

        $throttleKey = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many login attempts. Please try again in {$seconds} seconds.");

            return;
        }

        if (! Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey);
            $this->addError('email', 'Invalid credentials.');

            return;
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::guard('web')->user();
        $user->update(['last_login_at' => now()]);

        session()->regenerate();

        $this->redirect(route('admin.dashboard'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login');
    }
}
