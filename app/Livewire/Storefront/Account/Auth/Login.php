<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt([
            'email' => Str::lower($this->email),
            'password' => $this->password,
        ])) {
            $this->addError('email', 'The provided credentials are invalid.');

            return null;
        }

        session()->regenerate();

        return $this->redirect(session()->pull('url.intended', route('storefront.account.dashboard')), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('storefront.layouts.app', [
                'title' => 'Account login',
            ]);
    }
}
