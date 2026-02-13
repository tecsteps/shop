<?php

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Admin auth routes (no auth required)
Route::get('/admin/login', Login::class)->name('admin.login');

// Admin protected routes
Route::prefix('admin')->middleware(['auth', 'store.resolve'])->group(function () {
    Route::get('/', Dashboard::class)->name('admin.dashboard');
});
