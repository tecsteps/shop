<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Logout extends Component
{
    /**
     * Log the current user out and redirect to admin login.
     */
    public function logout(): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('admin.login');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.logout');
    }
}
