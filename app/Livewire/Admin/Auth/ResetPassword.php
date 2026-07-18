<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Reset password')]
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

    public function resetPassword(): void
    {
        $validated = $this->validate([
            'token' => ['required'], 'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
        ]);
        $validated['password_confirmation'] = $this->passwordConfirmation;
        $result = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($result === Password::PASSWORD_RESET) {
            session()->flash('status', __($result));
            $this->redirectRoute('admin.login', navigate: true);

            return;
        }

        $this->addError('email', __($result));
    }

    public function render(): View
    {
        return view('livewire.admin.auth.reset-password');
    }
}
