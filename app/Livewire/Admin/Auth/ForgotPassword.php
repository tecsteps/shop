<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $linkSent = false;

    /**
     * Authenticated admins have no business on the forgot-password page.
     */
    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect('/admin');
        }
    }

    /**
     * Send a reset link through the "users" broker (spec 06 §1.1). The
     * response is always generic so it never reveals whether the email
     * exists. The broker throttles to one email per 60 seconds.
     */
    public function sendResetLink(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        Password::broker('users')->sendResetLink(['email' => $this->email]);

        $this->linkSent = true;
    }

    /**
     * Render the forgot-password page on the centered auth layout.
     */
    public function render(): View
    {
        return view('livewire.admin.auth.forgot-password')
            ->layout('admin.layouts.auth')
            ->title('Forgot password');
    }
}
