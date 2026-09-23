<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ResetPassword extends \Livewire\Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): mixed
    {
        $data = $this->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);
        $status = Password::broker('customers')->reset($data, function (Customer $customer, string $password): void {
            $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordResetEvent($customer));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        session()->flash('status', 'Your password has been reset. Please sign in.');

        return $this->redirectRoute('storefront.login');
    }

    public function render(): mixed
    {
        return view('storefront.account.reset-password')->layout('storefront.layouts.app');
    }
}
