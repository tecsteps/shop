<?php

namespace App\Livewire\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): RedirectResponse|Redirector
    {
        /** @var \Illuminate\Auth\SessionGuard $guard */
        $guard = Auth::guard('web');
        $guard->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect('/');
    }
}
