<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to authenticate the admin user.
     */
    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $this->selectDefaultStore($user);

        session()->regenerate();

        return $this->redirectIntended(default: '/admin', navigate: true);
    }

    /**
     * Ensure the login request is not rate limited (5/min per email+IP).
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
        ]);
    }

    /**
     * The per-email-and-IP throttle key for login attempts.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /**
     * Set the session's current store to the user's first store, if any.
     */
    protected function selectDefaultStore(User $user): void
    {
        $store = $user->stores()->withoutGlobalScopes()->first();

        if ($store !== null) {
            session()->put('current_store_id', $store->id);
        }
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
