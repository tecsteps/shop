<?php

use Illuminate\Support\Facades\Route;

// Admin panel routes. Loaded inside the "web" middleware group (see bootstrap/app.php).
Route::prefix('admin')->name('admin.')->group(function (): void {
    // Authentication pages (Phase 6 replaces these placeholders with Livewire components).
    Route::get('/login', fn (): string => 'admin login')->name('login');
    Route::post('/login', fn (): string => 'admin login')->name('login.attempt');
    Route::get('/forgot-password', fn (): string => 'admin forgot password')->name('password.request');
    Route::post('/forgot-password', fn (): string => 'admin forgot password')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token): string => 'admin reset password')->name('password.reset');

    Route::middleware(['auth', 'verified', 'store.resolve:admin', 'role.check:owner,admin,staff,support'])->group(function (): void {
        Route::get('/', fn (): string => 'admin ok')->name('dashboard');
    });
});
