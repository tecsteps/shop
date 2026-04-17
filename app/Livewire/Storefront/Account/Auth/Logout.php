<?php

namespace App\Livewire\Storefront\Account\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class Logout
{
    public function __invoke(): RedirectResponse
    {
        Auth::guard('customer')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}
