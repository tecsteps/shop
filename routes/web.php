<?php

use App\Livewire\Admin\Analytics\Index as AdminAnalytics;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as AdminCollectionsForm;
use App\Livewire\Admin\Collections\Index as AdminCollections;
use App\Livewire\Admin\Customers\Index as AdminCustomers;
use App\Livewire\Admin\Customers\Show as AdminCustomersShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Discounts\Form as AdminDiscountsForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscounts;
use App\Livewire\Admin\Navigation\Index as AdminNavigation;
use App\Livewire\Admin\Orders\Index as AdminOrders;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Pages\Form as AdminPagesForm;
use App\Livewire\Admin\Pages\Index as AdminPages;
use App\Livewire\Admin\Products\Form as AdminProductsForm;
use App\Livewire\Admin\Products\Index as AdminProducts;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Settings\Shipping as AdminShipping;
use App\Livewire\Admin\Settings\Taxes as AdminTaxes;
use App\Livewire\Admin\Themes\Index as AdminThemes;
use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as CustomerDashboard;
use App\Livewire\Storefront\Account\Orders\Index as CustomerOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as CustomerOrdersShow;
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

        Route::get('dashboard', AdminDashboard::class)->name('dashboard');

        Route::get('products', AdminProducts::class)->name('products.index');
        Route::get('products/create', AdminProductsForm::class)->name('products.create');
        Route::get('products/{product}/edit', AdminProductsForm::class)->name('products.edit');

        Route::get('collections', AdminCollections::class)->name('collections.index');
        Route::get('collections/create', AdminCollectionsForm::class)->name('collections.create');
        Route::get('collections/{collection}/edit', AdminCollectionsForm::class)->name('collections.edit');

        Route::get('orders', AdminOrders::class)->name('orders.index');
        Route::get('orders/{order}', AdminOrdersShow::class)->name('orders.show');

        Route::get('customers', AdminCustomers::class)->name('customers.index');
        Route::get('customers/{customer}', AdminCustomersShow::class)->name('customers.show');

        Route::get('discounts', AdminDiscounts::class)->name('discounts.index');
        Route::get('discounts/create', AdminDiscountsForm::class)->name('discounts.create');
        Route::get('discounts/{discount}/edit', AdminDiscountsForm::class)->name('discounts.edit');

        Route::get('settings', AdminSettings::class)->name('settings.index');
        Route::get('settings/shipping', AdminShipping::class)->name('settings.shipping');
        Route::get('settings/taxes', AdminTaxes::class)->name('settings.taxes');

        Route::get('themes', AdminThemes::class)->name('themes.index');

        Route::get('pages', AdminPages::class)->name('pages.index');
        Route::get('pages/create', AdminPagesForm::class)->name('pages.create');
        Route::get('pages/{page}/edit', AdminPagesForm::class)->name('pages.edit');

        Route::get('navigation', AdminNavigation::class)->name('navigation.index');

        Route::get('analytics', AdminAnalytics::class)->name('analytics.index');
    });
});

// Storefront account routes
Route::prefix('account')->name('storefront.account.')->group(function () {
    Route::get('login', CustomerLogin::class)->name('login');
    Route::get('register', CustomerRegister::class)->name('register');

    Route::middleware(['customer.auth'])->group(function () {
        Route::get('/', CustomerDashboard::class)->name('dashboard');
        Route::get('/orders', CustomerOrdersIndex::class)->name('orders.index');
        Route::get('/orders/{orderNumber}', CustomerOrdersShow::class)->name('orders.show');
        Route::get('/addresses', AddressesIndex::class)->name('addresses.index');
        Route::post('/logout', function () {
            \Illuminate\Support\Facades\Auth::guard('customer')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('storefront.account.login');
        })->name('logout');
    });
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
