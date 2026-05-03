<?php

namespace App\Livewire\Admin\Auth;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['bool'],
        ]);

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'],
            ]);
        }

        if (! Auth::guard('web')->attempt([
            'email' => Str::lower($this->email),
            'password' => $this->password,
        ], $this->remember)) {
            RateLimiter::hit($key);

            $this->addError('email', 'Invalid credentials.');

            return null;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $user = Auth::user();
        $storeId = $user?->stores()->oldest((new Store)->getTable().'.id')->value((new Store)->getTable().'.id');

        if ($storeId !== null) {
            session()->put('current_store_id', $storeId);
        }

        $user?->forceFill(['last_login_at' => now()])->save();

        return $this->redirect(session()->pull('url.intended', route('admin.dashboard')), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.login')
            ->layout('layouts.auth', [
                'title' => 'Admin login',
            ]);
    }

    private function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }
}
