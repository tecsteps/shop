<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $resetLinkSent = false;

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        Password::broker('customers')->sendResetLink([
            'email' => Str::lower($this->email),
        ]);

        $this->resetLinkSent = true;

        session()->flash('status', 'We have emailed your password reset link!');
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.forgot-password')
            ->layout('storefront.layouts.app', [
                'title' => 'Forgot password',
            ]);
    }
}
