<?php

use App\Http\Controllers\Admin\AnalyticsExportController;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Apps\Show as AppShow;
use App\Livewire\Admin\Auth\ForgotPassword as AdminForgotPassword;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\ResetPassword as AdminResetPassword;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Collections\Index as CollectionIndex;
use App\Livewire\Admin\Customers\Index as CustomerIndex;
use App\Livewire\Admin\Customers\Show as CustomerShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountIndex;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Livewire\Admin\Orders\Index as OrderIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PageIndex;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Livewire\Admin\Search\Settings as SearchSettings;
use App\Livewire\Admin\SelectStore;
use App\Livewire\Admin\Settings\General as GeneralSettings;
use App\Livewire\Admin\Settings\Shipping as ShippingSettings;
use App\Livewire\Admin\Settings\Taxes as TaxSettings;
use App\Livewire\Admin\Themes\Editor as ThemeEditor;
use App\Livewire\Admin\Themes\Index as ThemeIndex;
use App\Livewire\Storefront\Account\Addresses\Index as AddressIndex;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as CustomerForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as CustomerResetPassword;
use App\Livewire\Storefront\Account\Dashboard as CustomerDashboard;
use App\Livewire\Storefront\Account\Orders\Index as CustomerOrderIndex;
use App\Livewire\Storefront\Account\Orders\Show as CustomerOrderShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as StorefrontCollectionIndex;
use App\Livewire\Storefront\Collections\Show as StorefrontCollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as StorefrontPageShow;
use App\Livewire\Storefront\Products\Show as StorefrontProductShow;
use App\Livewire\Storefront\Search\Index as StorefrontSearch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:login')->group(function (): void {
    Route::get('/admin/login', AdminLogin::class)->name('admin.login');
    Route::get('/admin/forgot-password', AdminForgotPassword::class)->name('admin.password.request');
    Route::get('/admin/reset-password/{token}', AdminResetPassword::class)->name('admin.password.reset');
    Route::get('/account/login', CustomerLogin::class)->middleware('store.resolve')->name('storefront.login');
    Route::get('/account/register', CustomerRegister::class)->middleware('store.resolve')->name('storefront.register');
    Route::get('/forgot-password', CustomerForgotPassword::class)->middleware('store.resolve')->name('storefront.password.request');
    Route::get('/reset-password/{token}', CustomerResetPassword::class)->middleware('store.resolve')->name('storefront.password.reset');
});

Route::post('/admin/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('admin.login')->withHeaders(['Cache-Control' => 'no-store']);
})->middleware('auth')->name('admin.logout');

Route::get('/admin/select-store', SelectStore::class)
    ->middleware(['auth', 'verified', 'can:view-admin'])
    ->name('admin.select-store');

Route::post('/account/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('customer')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('storefront.login')->withHeaders(['Cache-Control' => 'no-store']);
})->middleware('auth:customer')->name('storefront.logout');

Route::get('/oauth/authorize', fn () => response()->json(['message' => 'OAuth is not implemented.'], 501))->name('oauth.authorize');
Route::post('/oauth/token', fn () => response()->json(['message' => 'OAuth is not implemented.'], 501))->name('oauth.token');

Route::view('/dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'store.resolve', 'can:view-admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/products', ProductIndex::class)->name('products');
    Route::get('/products/create', ProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', ProductForm::class)->whereNumber('product')->name('products.edit');
    Route::get('/inventory', InventoryIndex::class)->name('inventory.index');
    Route::get('/collections', CollectionIndex::class)->name('collections');
    Route::get('/collections/create', CollectionForm::class)->name('collections.create');
    Route::get('/collections/{collection}/edit', CollectionForm::class)->whereNumber('collection')->name('collections.edit');
    Route::get('/orders', OrderIndex::class)->name('orders');
    Route::get('/orders/{order}', OrderShow::class)->whereNumber('order')->name('orders.show');
    Route::get('/customers', CustomerIndex::class)->name('customers');
    Route::get('/customers/{customer}', CustomerShow::class)->whereNumber('customer')->name('customers.show');
    Route::get('/discounts', DiscountIndex::class)->name('discounts');
    Route::get('/discounts/create', DiscountForm::class)->name('discounts.create');
    Route::get('/discounts/{discount}/edit', DiscountForm::class)->whereNumber('discount')->name('discounts.edit');
    Route::get('/pages', PageIndex::class)->name('pages');
    Route::get('/pages/create', PageForm::class)->name('pages.create');
    Route::get('/pages/{page}/edit', PageForm::class)->whereNumber('page')->name('pages.edit');
    Route::get('/settings', GeneralSettings::class)->name('settings');
    Route::get('/settings/shipping', ShippingSettings::class)->name('settings.shipping');
    Route::get('/settings/taxes', TaxSettings::class)->name('settings.taxes');
    Route::get('/themes', ThemeIndex::class)->name('themes');
    Route::get('/themes/{theme}/editor', ThemeEditor::class)->whereNumber('theme')->name('themes.editor');
    Route::get('/analytics', AnalyticsIndex::class)->name('analytics');
    Route::get('/analytics/exports/{analyticsExport}/download', [AnalyticsExportController::class, 'download'])
        ->whereNumber('analyticsExport')
        ->middleware('can:view-analytics')
        ->name('analytics.exports.download');
    Route::get('/developers', DevelopersIndex::class)->name('developers');
    Route::get('/navigation', NavigationIndex::class)->name('navigation');
    Route::get('/apps', AppsIndex::class)->name('apps');
    Route::get('/apps/{installation}', AppShow::class)->whereNumber('installation')->name('apps.show');
    Route::get('/search/settings', SearchSettings::class)->name('search.settings');
});

Route::middleware('store.resolve')->group(function (): void {
    Route::get('/', StorefrontHome::class)->name('home');
    Route::get('/collections', StorefrontCollectionIndex::class)->name('storefront.collections');
    Route::get('/collections/{handle}', StorefrontCollectionShow::class)->name('storefront.collection');
    Route::get('/products/{handle}', StorefrontProductShow::class)->name('storefront.product');
    Route::get('/cart', CartShow::class)->name('storefront.cart');
    Route::get('/search', StorefrontSearch::class)->middleware('throttle:search')->name('storefront.search');
    Route::get('/pages/{handle}', StorefrontPageShow::class)->name('storefront.page');
    Route::get('/checkout', CheckoutShow::class)->middleware('throttle:checkout')->name('storefront.checkout');
    Route::get('/checkout/{checkoutId}', CheckoutShow::class)->whereNumber('checkoutId')->middleware('throttle:checkout')->name('storefront.checkout.resume');
    Route::get('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->whereNumber('checkoutId')->middleware('signed')->name('storefront.confirmation');

    Route::middleware('auth:customer')->prefix('account')->name('storefront.account.')->group(function (): void {
        Route::get('/', CustomerDashboard::class)->name('dashboard');
        Route::get('/orders', CustomerOrderIndex::class)->name('orders');
        Route::get('/orders/{orderNumber}', CustomerOrderShow::class)->name('orders.show');
        Route::get('/addresses', AddressIndex::class)->name('addresses');
    });
});

require __DIR__.'/settings.php';
