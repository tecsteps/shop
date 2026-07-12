<?php

namespace App\Livewire\Admin\Auth;

use App\Models\StoreUser;
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

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect('/admin', navigate: true);
        }
    }

    public function authenticate(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::transliterate(Str::lower($this->email).'|'.request()->ip());
        $ipKey = 'login-ip:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.max(RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey)).' seconds.',
            ]);
        }

        if (! Auth::guard('web')->attempt($credentials, $this->remember)) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        $user = Auth::guard('web')->user();
        $status = $user?->status instanceof \BackedEnum ? $user->status->value : $user?->status;
        if ($status !== 'active') {
            Auth::guard('web')->logout();
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        $storeId = StoreUser::query()->where('user_id', $user?->getAuthIdentifier())->orderBy('store_id')->value('store_id');
        if (! $storeId) {
            Auth::guard('web')->logout();
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        RateLimiter::clear($key);
        RateLimiter::clear($ipKey);
        request()->session()->regenerate();

        $user?->forceFill(['last_login_at' => now()])->save();
        session(['current_store_id' => (int) $storeId]);

        $this->redirectIntended('/admin', navigate: true);
    }

    public function render(): View
    {
        return view('admin.auth.login')->layout('admin.layouts.auth', ['title' => 'Admin login']);
    }
}
