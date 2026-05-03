<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

trait UsesAdminStore
{
    protected function currentStore(): Store
    {
        return app('current_store');
    }

    protected function currentUser(): User
    {
        return Auth::user();
    }

    protected function money(int $amount, ?string $currency = null): string
    {
        return Number::currency($amount / 100, $currency ?? $this->currentStore()->default_currency);
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        session()->flash('admin_toast', [
            'message' => $message,
            'type' => $type,
        ]);

        $this->dispatch('toast', message: $message, type: $type);
    }
}
