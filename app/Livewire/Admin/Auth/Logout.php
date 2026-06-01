<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Logs the current admin user out: invalidates the session, regenerates the
 * CSRF token, and redirects to the admin login screen.
 */
class Logout
{
    public function __invoke()
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('admin.login');
    }
}
