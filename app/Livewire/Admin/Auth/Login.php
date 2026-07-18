<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Admin login')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'admin-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many login attempts. Please try again later.']);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        $user = Auth::user();
        $store = $user?->stores()->orderBy('stores.id')->first();

        if ($store === null) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Your account does not have access to a store.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();
        session()->put('current_store_id', $store->id);
        $user->update(['last_login_at' => now()]);
        $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.login');
    }
}
