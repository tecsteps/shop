<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Auth\CustomerPasswordBroker;
use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ResetPassword extends StorefrontComponent
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
            'token' => ['required'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required'],
        ], ['password.same' => 'The passwords do not match.']);

        $status = app(CustomerPasswordBroker::class)->reset([
            'email' => $this->email,
            'store_id' => $this->currentStore()->getKey(),
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'token' => $this->token,
        ], function (Customer $customer): void {
            $customer->forceFill(['password_hash' => Hash::make($this->password)])->save();
            event(new PasswordReset($customer));
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return null;
        }

        return $this->redirect(url('/account/login'), navigate: true);
    }

    public function render(): View
    {
        return $this->storefront(view('storefront.account.auth.reset-password'), 'Choose a new password - '.$this->currentStore()->name);
    }
}
