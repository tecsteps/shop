<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirectRoute('admin.login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.logout');
    }
}
