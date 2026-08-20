<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $credentials = $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::guard('web')->attempt($credentials, $this->remember)) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();
        $store = auth()->user()->stores()->first();

        if ($store !== null) {
            session()->put('current_store_id', $store->getKey());
        }

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.login')->layout('layouts.auth');
    }
}
