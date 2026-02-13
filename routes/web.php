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

// Fortify default dashboard route (used by Fortify redirects)
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', AdminLogin::class)->name('login');

    Route::middleware(['auth', 'store.resolve:admin'])->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });

        Route::get('dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    });
});

// Storefront account routes
Route::prefix('account')->name('storefront.account.')->group(function () {
    Route::get('login', CustomerLogin::class)->name('login');
    Route::get('register', CustomerRegister::class)->name('register');
});

// Home route (also aliased as 'home' for auth layout views)
Route::get('/', Home::class)->middleware(['store.resolve'])->name('home');

// Storefront routes
Route::middleware(['store.resolve'])->name('storefront.')->group(function () {
    Route::get('/collections', CollectionsIndex::class)->name('collections.index');
    Route::get('/collections/{handle}', CollectionsShow::class)->name('collections.show');
    Route::get('/products/{handle}', ProductsShow::class)->name('products.show');
    Route::get('/cart', CartShow::class)->name('cart');
    Route::get('/search', SearchIndex::class)->name('search');
    Route::get('/pages/{handle}', PagesShow::class)->name('pages.show');
});

require __DIR__.'/settings.php';
