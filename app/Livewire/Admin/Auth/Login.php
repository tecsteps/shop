<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): mixed
    {
        $this->validate();

        $throttleKey = 'login:'.$this->getIp();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Try again in :seconds seconds.', ['seconds' => $seconds]),
            ]);
        }

        if (! Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        return redirect()->intended('/admin');
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login');
    }

    protected function getIp(): string
    {
        return request()->ip() ?? '127.0.0.1';
    }
}
