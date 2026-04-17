<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function __invoke(): mixed
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/admin/login');
    }

    public function logout(): mixed
    {
        return $this->__invoke();
    }

    public function render(): mixed
    {
        return view('livewire.admin.auth.logout');
    }
}
