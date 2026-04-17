<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

        $key = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many login attempts. Please try again in a minute.');

            return null;
        }

        if (! Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('email', 'These credentials do not match our records.');

            return null;
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $storeId = DB::table('store_users')->where('user_id', $user->id)->value('store_id');

        if ($storeId) {
            session()->put('current_store_id', $storeId);
        }

        return $this->redirect('/admin', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
