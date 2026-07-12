<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Auth\CustomerPasswordBroker;
use App\Livewire\Storefront\StorefrontComponent;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPassword extends StorefrontComponent
{
    public string $email = '';

    public bool $sent = false;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        app(CustomerPasswordBroker::class)->sendResetLink([
            'email' => $this->email,
            'store_id' => $this->currentStore()->getKey(),
        ]);

        $this->sent = true;
    }

    public function render(): View
    {
        return $this->storefront(view('storefront.account.auth.forgot-password'), 'Reset your password - '.$this->currentStore()->name);
    }
}
