<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Store;
use App\Services\CustomerPasswordResetService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResetPassword extends Component
{
    #[Locked]
    public int $storeId;

    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
        $this->token = $token;
        $this->email = (string) request('email', '');
    }

    public function resetPassword(CustomerPasswordResetService $passwords): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $reset = $passwords->reset(
            Store::query()->findOrFail($this->storeId),
            $validated['email'],
            $this->token,
            $validated['password'],
        );

        if (! $reset) {
            $this->addError('email', __('This password reset link is invalid or has expired.'));

            return;
        }

        session()->flash('status', __('Your password has been reset. You may log in with your new password.'));

        $this->redirectRoute('account.login', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.reset-password')
            ->layout('layouts.auth');
    }
}
