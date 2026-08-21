<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ResetPassword extends Component
{
    public string $token;

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email')->toString();
    }

    public function resetPassword(): void
    {
        $data = $this->validate(['email' => ['required', 'email'], 'password' => ['required', 'min:8', 'same:passwordConfirmation']]);
        $status = Password::broker('customers')->reset(['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $this->passwordConfirmation, 'token' => $this->token], function (Customer $customer, string $password): void {
            $customer->password_hash = $password;
            $customer->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        $this->redirect(route('account.login'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.reset-password')->layout('layouts.storefront');
    }
}
