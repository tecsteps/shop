<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class ForgotPassword extends Component
{
    public string $email = '';

    public string $status = '';

    public function sendLink(): void
    {
        $this->validate(['email' => 'required|email']);

        $response = Password::broker('customers')->sendResetLink(['email' => $this->email]);

        $this->status = $response === Password::RESET_LINK_SENT
            ? 'If an account exists for that email we have sent a reset link.'
            : 'If an account exists for that email we have sent a reset link.';
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.forgot-password');
    }
}
