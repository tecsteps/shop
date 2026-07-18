<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);
        $result = Password::sendResetLink(['email' => $this->email]);
        $result === Password::RESET_LINK_SENT
            ? $this->status = __($result)
            : $this->addError('email', __($result));
    }

    public function render(): View
    {
        return view('livewire.admin.auth.forgot-password');
    }
}
