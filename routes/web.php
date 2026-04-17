<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as AdminCollectionForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomerShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Discounts\Form as AdminDiscountForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Inventory\Index as AdminInventoryIndex;
use App\Livewire\Admin\Navigation\Index as AdminNavigationIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Livewire\Admin\Pages\Form as AdminPageForm;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Form as AdminProductForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Settings\General as AdminSettingsGeneral;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Storefront\Account\Auth\Login as AccountLogin;
use App\Livewire\Storefront\Account\Auth\Register as AccountRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Index as CheckoutIndex;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as PageShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
 * Admin auth
 */
Route::prefix('admin')->group(function (): void {
    Route::get('login', AdminLogin::class)->middleware('guest')->name('admin.login');
    Route::post('logout', function () {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/admin/login');
    })->middleware('auth')->name('admin.logout');
});

/*
 * Admin
 */
Route::prefix('admin')->middleware(['auth', 'store.resolve:admin'])->group(function (): void {
    Route::get('/', AdminDashboard::class)->name('admin.dashboard');
    Route::get('products', AdminProductsIndex::class)->name('admin.products.index');
    Route::get('products/create', AdminProductForm::class)->name('admin.products.create');
    Route::get('products/{product}/edit', AdminProductForm::class)->name('admin.products.edit');
    Route::get('collections', AdminCollectionsIndex::class)->name('admin.collections.index');
    Route::get('collections/create', AdminCollectionForm::class)->name('admin.collections.create');
    Route::get('collections/{collection}/edit', AdminCollectionForm::class)->name('admin.collections.edit');
    Route::get('inventory', AdminInventoryIndex::class)->name('admin.inventory.index');
    Route::get('orders', AdminOrdersIndex::class)->name('admin.orders.index');
    Route::get('orders/{order}', AdminOrderShow::class)->name('admin.orders.show');
    Route::get('customers', AdminCustomersIndex::class)->name('admin.customers.index');
    Route::get('customers/{customer}', AdminCustomerShow::class)->name('admin.customers.show');
    Route::get('discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
    Route::get('discounts/create', AdminDiscountForm::class)->name('admin.discounts.create');
    Route::get('discounts/{discount}/edit', AdminDiscountForm::class)->name('admin.discounts.edit');
    Route::get('pages', AdminPagesIndex::class)->name('admin.pages.index');
    Route::get('pages/create', AdminPageForm::class)->name('admin.pages.create');
    Route::get('pages/{page}/edit', AdminPageForm::class)->name('admin.pages.edit');
    Route::get('navigation', AdminNavigationIndex::class)->name('admin.navigation.index');
    Route::get('settings/shipping', AdminSettingsShipping::class)->name('admin.settings.shipping');
    Route::get('settings/taxes', AdminSettingsTaxes::class)->name('admin.settings.taxes');
    Route::get('settings', AdminSettingsGeneral::class)->name('admin.settings.general');
});

/*
 * Storefront
 */
Route::middleware(['store.resolve:storefront'])->group(function (): void {
    Route::get('/', StorefrontHome::class)->name('storefront.home');
    Route::get('collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('collections/{handle}', CollectionShow::class)->name('storefront.collections.show');
    Route::get('products/{handle}', ProductShow::class)->name('storefront.products.show');
    Route::get('cart', CartShow::class)->name('storefront.cart.show');
    Route::get('checkout', CheckoutIndex::class)->name('storefront.checkout');
    Route::get('checkout/{orderNumber}/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
    Route::get('search', SearchIndex::class)->name('storefront.search');
    Route::get('pages/{handle}', PageShow::class)->name('storefront.pages.show');

    Route::get('account/login', AccountLogin::class)->middleware('guest:customer')->name('storefront.account.login');
    Route::get('account/register', AccountRegister::class)->middleware('guest:customer')->name('storefront.account.register');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('account', AccountDashboard::class)->name('storefront.account.dashboard');
        Route::post('account/logout', function () {
            Auth::guard('customer')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect(route('storefront.home'));
        })->name('storefront.account.logout');
    });
});
