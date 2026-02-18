<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin-auth')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->validate();

        $throttleKey = 'login|'.$this->getRequestIp();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many attempts. Try again in {$seconds} seconds.");

            return;
        }

        /** @var \Illuminate\Auth\SessionGuard $guard */
        $guard = Auth::guard('web');

        if ($guard->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            /** @var User $user */
            $user = $guard->user();
            $user->update(['last_login_at' => now()]);

            Log::channel('json')->info('Admin login success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $this->getRequestIp(),
            ]);

            $this->redirect('/admin');

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        Log::channel('json')->warning('Admin login failed', [
            'email' => $this->email,
            'ip' => $this->getRequestIp(),
        ]);

        $this->addError('email', 'Invalid credentials');
    }

    protected function getRequestIp(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.auth.login');
    }
}
