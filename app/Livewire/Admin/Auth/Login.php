<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

#[\Livewire\Attributes\Layout('layouts.auth')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);
        $key = Str::transliterate(Str::lower($validated['email']).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many sign-in attempts. Please try again shortly.']);
        }

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password'], 'status' => 'active'], $validated['remember'])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        /** @var User $user */
        $user = Auth::user();
        $store = $user->stores()->first();

        if ($store === null) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Your account is not assigned to a store.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();
        session(['current_store_id' => $store->getKey()]);
        $user->update(['last_login_at' => now()]);
        $this->redirect('/admin', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
