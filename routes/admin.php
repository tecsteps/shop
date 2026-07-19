<?php

use App\Http\Controllers\Admin\Auth\EmailVerificationController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

// Admin panel routes. Loaded inside the "web" middleware group (see bootstrap/app.php).
Route::prefix('admin')->name('admin.')->group(function (): void {
    // Auth pages (spec 02 §1.1). Submissions are handled by Livewire actions.
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::post('/logout', LogoutController::class)->name('logout');

    Route::middleware(['auth', 'verified', 'store.resolve:admin', 'role.check:owner,admin,staff,support'])->group(function (): void {
        Route::get('/', fn (): string => 'admin ok')->name('dashboard');
    });
});

// Email verification for admin users. Route names are fixed by Laravel's
// "verified" middleware and MustVerifyEmail notification.
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
