<?php

namespace App\Livewire\Storefront\Account\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    public function register(): mixed
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'marketing_opt_in' => 'boolean',
        ]);

        // Customer model creation is introduced in Phase 6. This component will
        // be completed then. For now registration is intentionally a no-op to
        // avoid creating placeholder data before the customers table exists.
        return redirect('/account/login');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.auth.register');
    }
}
