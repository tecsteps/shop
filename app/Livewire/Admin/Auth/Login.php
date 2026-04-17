<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): mixed
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        if (! Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        Session::regenerate();

        $user = Auth::guard('web')->user();

        $firstStore = $user?->stores()->first();

        if ($firstStore !== null) {
            Session::put('current_store_id', $firstStore->id);
        }

        $user?->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended('/admin');
    }

    protected function throttleKey(): string
    {
        return 'login:'.strtolower($this->email).'|'.request()->ip();
    }

    #[Layout('components.layouts.admin-auth')]
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.auth.login');
    }
}
