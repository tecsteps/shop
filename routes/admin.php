<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| The admin panel. Auth pages use only the `web` group. Authenticated pages add
| `auth` plus the `admin` middleware group (which resolves the current store
| from the session and verifies membership). Later phases add the full panel;
| Phase 1 establishes login/logout and a dashboard placeholder.
|
*/

Route::prefix('admin')->group(function () {
    // Guest-only authentication pages.
    Route::middleware('guest:web')->group(function () {
        Route::livewire('/login', AdminLogin::class)->name('admin.login');
    });

    Route::post('/logout', AdminLogout::class)
        ->middleware('auth:web')
        ->name('admin.logout');

    // Authenticated admin pages (store resolved from session).
    Route::middleware(['auth:web', 'admin'])->group(function () {
        Route::view('/', 'admin.dashboard')->name('admin.dashboard');
    });
});
