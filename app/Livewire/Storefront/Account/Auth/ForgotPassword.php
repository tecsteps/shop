<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Store;
use App\Services\CustomerPasswordResetService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ForgotPassword extends Component
{
    #[Locked]
    public int $storeId;

    public string $email = '';

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
    }

    public function send(CustomerPasswordResetService $passwords): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $passwords->sendResetLink(
            Store::query()->findOrFail($this->storeId),
            $validated['email'],
        );

        session()->flash('status', __('If an account matches that email, a reset link has been sent.'));
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.forgot-password')
            ->layout('layouts.auth');
    }
}
