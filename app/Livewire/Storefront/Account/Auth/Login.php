<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Livewire\Concerns\ThrottlesLoginAttempts;
use App\Livewire\Storefront\Concerns\MergesGuestCartOnLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Login extends Component
{
    use MergesGuestCartOnLogin, ThrottlesLoginAttempts;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public ?string $errorMessage = null;

    /**
     * Authenticated customers are sent straight to their account.
     */
    public function mount(): void
    {
        if (Auth::guard('customer')->check()) {
            $this->redirect('/account');
        }
    }

    /**
     * Attempt to authenticate against the store-scoped customer guard
     * (spec 06 §1.2). The failure message is always generic.
     */
    public function login(): void
    {
        $this->errorMessage = null;

        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::guard('customer')->attempt($credentials, $this->remember)) {
            $this->hitLoginRateLimiter();

            $this->errorMessage = 'Invalid credentials.';

            return;
        }

        session()->regenerate();
        $this->clearLoginRateLimiter();

        $customer = Auth::guard('customer')->user();

        $this->mergeGuestCartOnLogin($customer);

        $this->redirect(session()->pull('url.intended', '/account'));
    }

    /**
     * Render the login page in the storefront layout (spec 04 §10.1).
     */
    public function render(): View
    {
        return view('livewire.storefront.account.auth.login')
            ->layout('storefront.layouts.app')
            ->title('Log in');
    }
}
