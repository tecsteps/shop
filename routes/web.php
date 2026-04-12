<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login as AccountLogin;
use App\Livewire\Storefront\Account\Auth\Register as AccountRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrdersShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', StorefrontHome::class)->name('home');
Route::get('/storefront', StorefrontHome::class)->name('storefront.home');

Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
Route::get('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
Route::get('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
Route::get('/cart', CartShow::class)->name('storefront.cart.show');
Route::get('/checkout', CheckoutShow::class)->name('storefront.checkout.show');
Route::get('/checkout/confirmation/{order_number}', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
Route::get('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');
Route::get('/search', SearchIndex::class)->name('storefront.search');

Route::get('/account/login', AccountLogin::class)->name('storefront.account.login');
Route::get('/account/register', AccountRegister::class)->name('storefront.account.register');

Route::post('/account/logout', function (Request $request) {
    Auth::guard('customer')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('storefront.account.login');
})->name('storefront.account.logout');

Route::middleware('auth:customer')
    ->prefix('account')
    ->name('storefront.account.')
    ->group(function (): void {
        Route::get('/', AccountDashboard::class)->name('dashboard');
        Route::get('/orders', AccountOrdersIndex::class)->name('orders.index');
        Route::get('/orders/{orderNumber}', AccountOrdersShow::class)->name('orders.show');
        Route::get('/addresses', AccountAddressesIndex::class)->name('addresses.index');
    });

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
