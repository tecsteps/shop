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

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): mixed
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'same:password'],
        ]);

        $status = Password::broker('customers')->reset([
            'token' => $this->token,
            'email' => Str::lower($this->email),
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ], function (Customer $customer, string $password): void {
            $customer->forceFill([
                'password' => $password,
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'This password reset link is invalid or has expired.');

            return null;
        }

        session()->flash('status', 'Your password has been reset.');

        return $this->redirect(route('storefront.account.login'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.reset-password')
            ->layout('storefront.layouts.app', [
                'title' => 'Reset password',
            ]);
    }
}
