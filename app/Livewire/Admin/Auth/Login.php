<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin-auth')]
#[Title('Sign in - Admin')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $throttleKey = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('email', 'Too many login attempts. Please try again later.');

            return;
        }

        if (Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            $user = Auth::guard('web')->user();
            $user->update(['last_login_at' => now()]);

            $firstStore = $user->stores()->first();
            if ($firstStore) {
                session(['current_store_id' => $firstStore->id]);
            }

            $this->redirect(route('admin.dashboard'), navigate: true);

            return;
        }

        RateLimiter::hit($throttleKey, 60);
        $this->addError('email', 'These credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
