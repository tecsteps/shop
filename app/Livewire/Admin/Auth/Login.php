<?php

namespace App\Livewire\Admin\Auth;

use App\Livewire\Concerns\ThrottlesLoginAttempts;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Login extends Component
{
    use ThrottlesLoginAttempts;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public ?string $errorMessage = null;

    /**
     * Authenticated admins have no business on the login page.
     */
    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect('/admin');
        }
    }

    /**
     * Attempt to authenticate against the web guard (spec 06 §1.1). The
     * failure message is always generic so it never reveals which field
     * was wrong.
     */
    public function login(): void
    {
        $this->errorMessage = null;

        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::guard('web')->attempt($credentials, $this->remember)) {
            $this->hitLoginRateLimiter();

            $this->errorMessage = 'Invalid credentials.';

            return;
        }

        session()->regenerate();

        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $storeId = $user->stores()->value('stores.id');

        if ($storeId === null) {
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            $this->errorMessage = 'You do not have access to any store.';

            return;
        }

        session(['current_store_id' => $storeId]);
        $this->clearLoginRateLimiter();

        $this->redirect('/admin');
    }

    /**
     * Render the login page on the centered auth layout.
     */
    public function render(): View
    {
        return view('livewire.admin.auth.login')
            ->layout('admin.layouts.auth')
            ->title('Log in');
    }
}
