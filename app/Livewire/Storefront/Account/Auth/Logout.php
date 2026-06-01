<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Logs the current customer out and redirects to the storefront login screen.
 */
class Logout
{
    public function __invoke()
    {
        Auth::guard('customer')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('account.login');
    }
}
