<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Admin Login')]
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

        $throttleKey = 'login:'.$this->getIp();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            session()->flash('status', "Too many attempts. Try again in {$seconds} seconds.");

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        if (! Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            session()->flash('status', 'Invalid credentials.');

            return;
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirectIntended(default: '/admin');
    }

    protected function getIp(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }
}
