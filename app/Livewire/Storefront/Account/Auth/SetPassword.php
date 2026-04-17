<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.auth')]
class SetPassword extends Component
{
    #[Url]
    public string $token = '';

    #[Url]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function setPassword(): mixed
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required|string',
        ]);

        $status = Password::broker('customers')->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (Customer $customer, string $password): void {
                $customer->password_hash = Hash::make($password);
                if ($customer->email_verified_at === null) {
                    $customer->email_verified_at = now();
                }
                $customer->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Invalid or expired link.']);
        }

        Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password]);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect('/account');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.set-password');
    }
}
