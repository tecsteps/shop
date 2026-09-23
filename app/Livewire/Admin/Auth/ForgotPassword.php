<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Password;

class ForgotPassword extends \Livewire\Component
{
    public string $email = '';

    public string $statusMessage = '';

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email', 'max:255']]);
        Password::broker('users')->sendResetLink(['email' => $this->email]);
        $this->statusMessage = 'If an account with that email exists, we sent a password reset link.';
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.forgot-password')->layout('layouts.auth');
    }
}
