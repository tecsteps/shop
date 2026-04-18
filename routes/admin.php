<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/login', AdminLogin::class)->name('login');

Route::middleware(['auth', 'resolve.store:admin'])->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('logout');
});
