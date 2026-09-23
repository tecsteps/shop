<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Password;

class ForgotPassword extends \Livewire\Component
{
    public string $email = '';

    public string $statusMessage = '';

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email', 'max:255']]);
        Password::broker('customers')->sendResetLink(['email' => $this->email]);
        $this->statusMessage = 'If an account with that email exists, we sent a password reset link.';
    }

    public function render(): mixed
    {
        return view('storefront.account.forgot-password')->layout('storefront.layouts.app');
    }
}
