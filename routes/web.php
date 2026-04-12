<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Home as StorefrontHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', StorefrontHome::class)->name('home');
Route::get('/storefront', StorefrontHome::class)->name('storefront.home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('/admin/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('admin.login');
})->middleware('auth')->name('admin.logout');

require __DIR__.'/settings.php';
