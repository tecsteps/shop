<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PageShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy compatibility route
|--------------------------------------------------------------------------
*/

Route::redirect('dashboard', '/admin')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)->name('admin.login');

    Route::middleware(['auth:web'])->group(function () {
        Route::post('logout', [AdminLogout::class, 'logout'])->name('admin.logout');

        Route::middleware(['resolve.store:admin'])->group(function () {
            Route::get('/', function () {
                return view('admin.dashboard');
            })->name('admin.dashboard');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';

Route::middleware(['resolve.store:storefront'])->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', ProductShow::class)->name('storefront.products.show');
    Route::get('/cart', CartShow::class)->name('storefront.cart');
    Route::get('/search', SearchIndex::class)->name('storefront.search');
    Route::get('/pages/{handle}', PageShow::class)->name('storefront.pages.show');

    Route::get('account/login', CustomerLogin::class)->name('customer.login');
    Route::get('account/register', CustomerRegister::class)->name('customer.register');

    Route::middleware(['auth:customer'])->prefix('account')->group(function () {
        Route::post('logout', function () {
            auth()->guard('customer')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('customer.login');
        })->name('customer.logout');

        Route::get('/', function () {
            return view('storefront.account.dashboard');
        })->name('customer.dashboard');

        Route::get('/orders', function () {
            return view('storefront.account.orders.index');
        })->name('customer.orders');

        Route::get('/orders/{orderNumber}', function () {
            return view('storefront.account.orders.show');
        })->name('customer.orders.show');

        Route::get('/addresses', function () {
            return view('storefront.account.addresses.index');
        })->name('customer.addresses');
    });
});
