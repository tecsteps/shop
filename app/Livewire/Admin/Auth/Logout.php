<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout(): mixed
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirectRoute('admin.login');
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.logout')->layout('layouts.admin');
    }
}
