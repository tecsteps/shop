<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['storefront'])->group(function () {
    Route::get('/', \App\Livewire\Storefront\Home::class)->name('home');
    Route::get('/collections', \App\Livewire\Storefront\Collections\Index::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', \App\Livewire\Storefront\Collections\Show::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', \App\Livewire\Storefront\Products\Show::class)->name('storefront.products.show');
    Route::get('/cart', \App\Livewire\Storefront\Cart\Show::class)->name('storefront.cart');
    Route::get('/search', \App\Livewire\Storefront\Search\Index::class)->name('storefront.search');
    Route::get('/pages/{handle}', \App\Livewire\Storefront\Pages\Show::class)->name('storefront.pages.show');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('admin')->group(function () {
    Route::get('login', \App\Livewire\Admin\Auth\Login::class)
        ->name('admin.login');

    Route::post('logout', function () {
        auth()->guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::get('/', function () {
        return view('admin.dashboard');
    })->middleware(['auth'])->name('admin.dashboard');
});

Route::prefix('account')->middleware(['storefront'])->group(function () {
    Route::get('login', \App\Livewire\Storefront\Account\Auth\Login::class)
        ->name('storefront.login');

    Route::get('register', \App\Livewire\Storefront\Account\Auth\Register::class)
        ->name('storefront.register');

    Route::get('/', function () {
        return view('storefront.account.dashboard');
    })->middleware(['auth.customer'])->name('storefront.account');

    Route::post('logout', function () {
        auth()->guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('storefront.login');
    })->middleware(['auth.customer'])->name('storefront.logout');
});

require __DIR__.'/settings.php';
