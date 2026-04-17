<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\Logout as AdminLogout;
use App\Livewire\Admin\Collections\Form as AdminCollectionForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomerShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Discounts\Form as AdminDiscountForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Livewire\Admin\Products\Form as AdminProductForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Storefront\Account\Addresses\Index as CustomerAddresses;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as CustomerDashboard;
use App\Livewire\Storefront\Account\Orders\Index as CustomerOrders;
use App\Livewire\Storefront\Account\Orders\Show as CustomerOrderShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PageShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Auth;
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
            Route::get('/', AdminDashboard::class)->name('admin.dashboard');

            Route::get('/products', AdminProductsIndex::class)->name('admin.products.index');
            Route::get('/products/create', AdminProductForm::class)->name('admin.products.create');
            Route::get('/products/{product}/edit', AdminProductForm::class)->name('admin.products.edit');

            Route::get('/collections', AdminCollectionsIndex::class)->name('admin.collections.index');
            Route::get('/collections/create', AdminCollectionForm::class)->name('admin.collections.create');
            Route::get('/collections/{collection}/edit', AdminCollectionForm::class)->name('admin.collections.edit');

            Route::get('/orders', AdminOrdersIndex::class)->name('admin.orders.index');
            Route::get('/orders/{order}', AdminOrderShow::class)->name('admin.orders.show');

            Route::get('/customers', AdminCustomersIndex::class)->name('admin.customers.index');
            Route::get('/customers/{customer}', AdminCustomerShow::class)->name('admin.customers.show');

            Route::get('/discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
            Route::get('/discounts/create', AdminDiscountForm::class)->name('admin.discounts.create');
            Route::get('/discounts/{discount}/edit', AdminDiscountForm::class)->name('admin.discounts.edit');

            Route::get('/settings', AdminSettingsIndex::class)->name('admin.settings.index');
            Route::get('/settings/shipping', AdminSettingsShipping::class)->name('admin.settings.shipping');
            Route::get('/settings/taxes', AdminSettingsTaxes::class)->name('admin.settings.taxes');
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
        Route::get('/', CustomerDashboard::class)->name('customer.dashboard');
        Route::get('/orders', CustomerOrders::class)->name('customer.orders');
        Route::get('/orders/{orderNumber}', CustomerOrderShow::class)->name('customer.orders.show');
        Route::get('/addresses', CustomerAddresses::class)->name('customer.addresses');
        Route::post('/logout', function () {
            Auth::guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('customer.login');
        })->name('customer.logout');
    });
});
