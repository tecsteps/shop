<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $errorMessage = null;

    /**
     * Keep the token from the route and prefill the email from the link.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    /**
     * Reset the password through the "users" broker (spec 06 §1.1).
     */
    public function resetPassword(): void
    {
        $this->errorMessage = null;

        $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('users')->reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password_hash' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->errorMessage = 'This password reset link is invalid or has expired.';

            return;
        }

        session()->flash('status', 'Your password has been reset. You can now log in.');

        $this->redirect(route('admin.login'));
    }

    /**
     * Render the reset-password page on the centered auth layout.
     */
    public function render(): View
    {
        return view('livewire.admin.auth.reset-password')
            ->layout('admin.layouts.auth')
            ->title('Reset password');
    }
}
