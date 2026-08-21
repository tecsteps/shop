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
        $credentials = $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'admin-login|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Try again later.');

            return;
        }

        RateLimiter::hit($key, 60);

        if (! Auth::guard('web')->attempt([...$credentials, 'status' => 'active'], $this->remember)) {
            $this->addError('email', 'Invalid credentials');

            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();
        $store = auth()->user()->stores()->first();

        if ($store !== null) {
            session()->put('current_store_id', $store->getKey());
        }

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login')->layout('layouts.auth');
    }
}
