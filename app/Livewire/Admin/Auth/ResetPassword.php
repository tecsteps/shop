<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
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
        $status = Password::broker('users')->reset(['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $this->passwordConfirmation, 'token' => $this->token], function (User $user, string $password): void {
            $user->password = $password;
            $user->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        $this->redirect(route('admin.login'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.reset-password')->layout('layouts.auth');
    }
}
