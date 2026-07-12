<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);
        Password::broker('users')->sendResetLink(['email' => $this->email]);
        $this->sent = true;
    }

    public function render(): View
    {
        return view('admin.auth.forgot-password')->layout('admin.layouts.auth', ['title' => 'Reset your password']);
    }
}
