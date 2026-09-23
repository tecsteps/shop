<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(): mixed
    {
        $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'admin-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many login attempts. Try again in a minute.']);
        }

        RateLimiter::hit($key, 60);

        if (! Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password, 'status' => 'active'], $this->remember)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $storeId = $user->stores()->orderBy('stores.id')->value('stores.id');

        if (! $storeId) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages(['email' => 'This account does not have access to a store.']);
        }

        RateLimiter::clear($key);
        $user->forceFill(['last_login_at' => now()])->save();
        session()->regenerate();
        session()->put('current_store_id', $storeId);

        return $this->redirectRoute('admin.dashboard');
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login')->layout('layouts.auth');
    }
}
