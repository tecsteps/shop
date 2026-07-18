<?php

namespace App\Livewire\Storefront\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current customer out of the storefront.
     */
    public function __invoke()
    {
        Auth::guard('customer')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('home');
    }
}
