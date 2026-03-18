<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('admin.login'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.logout');
    }
}
