<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public string $message = '';

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);
        Password::broker('customers')->sendResetLink(['email' => $this->email]);
        $this->message = 'If an account exists for that email, a reset link has been sent.';
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.forgot-password')->layout('layouts.storefront');
    }
}
