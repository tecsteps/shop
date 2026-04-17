<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout(): void
    {
        /** @var \Illuminate\Auth\SessionGuard $guard */
        $guard = Auth::guard('web');
        $guard->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/admin/login');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.auth.logout');
    }
}
