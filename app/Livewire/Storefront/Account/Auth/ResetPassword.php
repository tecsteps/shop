<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
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
     * Reset the password through the store-scoped "customers" broker
     * (spec 06 §1.2). Both the token and the customer lookup are scoped
     * to the current store.
     */
    public function resetPassword(): void
    {
        $this->errorMessage = null;

        $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('customers')->reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password): void {
                $customer->forceFill([
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

        $this->redirect(route('storefront.account.login'));
    }

    /**
     * Render the reset-password page in the storefront layout.
     */
    public function render(): View
    {
        return view('livewire.storefront.account.auth.reset-password')
            ->layout('storefront.layouts.app')
            ->title('Reset password');
    }
}
