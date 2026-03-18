<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin auth routes
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)
        ->middleware('guest')
        ->name('admin.login');

    Route::post('logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
    });
});

// Storefront routes
Route::middleware(['storefront'])->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::get('/cart', CartShow::class)->name('storefront.cart');
    Route::get('/search', SearchIndex::class)->name('storefront.search');
    Route::get('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');

    Route::get('account/login', CustomerLogin::class)->name('storefront.login');
    Route::get('account/register', CustomerRegister::class)->name('storefront.register');

    Route::post('account/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.login');
    })->name('storefront.logout');

    Route::middleware(['auth:customer'])->group(function () {
        Route::get('account', function () {
            return view('storefront.account');
        })->name('storefront.account');
    });
});

require __DIR__.'/settings.php';
