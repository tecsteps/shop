<?php

namespace App\Livewire\Admin\Auth;

use App\Enums\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): mixed
    {
        $this->validate();

        $key = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Please try again in a minute.'),
            ]);
        }

        $user = \App\Models\User::query()->where('email', $this->email)->first();

        if (! $user || $user->status !== UserStatus::Active || ! Auth::guard('web')->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        RateLimiter::clear($key);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $firstStoreId = $user->stores()->value('stores.id');
        if ($firstStoreId && request()->hasSession()) {
            request()->session()->put('current_store_id', $firstStoreId);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
