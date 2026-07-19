<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $linkSent = false;

    /**
     * Authenticated customers are sent straight to their account.
     */
    public function mount(): void
    {
        if (Auth::guard('customer')->check()) {
            $this->redirect('/account');
        }
    }

    /**
     * Send a reset link through the store-scoped "customers" broker
     * (spec 06 §1.2). The response is always generic so it never reveals
     * whether the email exists in this store.
     */
    public function sendResetLink(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        Password::broker('customers')->sendResetLink(['email' => $this->email]);

        $this->linkSent = true;
    }

    /**
     * Render the forgot-password page in the storefront layout.
     */
    public function render(): View
    {
        return view('livewire.storefront.account.auth.forgot-password')
            ->layout('storefront.layouts.app')
            ->title('Forgot password');
    }
}
