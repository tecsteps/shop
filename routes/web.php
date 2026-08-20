<?php

use App\Livewire\Admin\Analytics\Index as AdminAnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AdminAppsIndex;
use App\Livewire\Admin\Apps\Show as AdminAppsShow;
use App\Livewire\Admin\Auth\ForgotPassword as AdminForgotPassword;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\ResetPassword as AdminResetPassword;
use App\Livewire\Admin\Collections\Create as AdminCollectionsCreate;
use App\Livewire\Admin\Collections\Edit as AdminCollectionsEdit;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomersShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopersIndex;
use App\Livewire\Admin\Discounts\Form as AdminDiscountForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Inventory\Index as AdminInventoryIndex;
use App\Livewire\Admin\Navigation\Index as AdminNavigationIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Pages\Create as AdminPagesCreate;
use App\Livewire\Admin\Pages\Edit as AdminPagesEdit;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Form as AdminProductForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Search\Settings as AdminSearchSettings;
use App\Livewire\Admin\Settings\General as AdminSettingsGeneral;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemesEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as CustomerForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as CustomerResetPassword;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrders;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrderShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PageShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';

Route::get('/favicon.ico', fn (): \Symfony\Component\HttpFoundation\Response => response('', 204))->name('favicon');

Route::middleware('store.resolve')->group(function (): void {
    Route::livewire('/', Home::class)->name('home');
    Route::livewire('/collections', CollectionsIndex::class)->name('collections.index');
    Route::livewire('/collections/{handle}', CollectionShow::class)->name('collection.show');
    Route::livewire('/products/{handle}', ProductShow::class)->name('product.show');
    Route::livewire('/cart', CartShow::class)->name('cart.show');
    Route::livewire('/search', SearchIndex::class)->name('search');
    Route::livewire('/pages/{handle}', PageShow::class)->name('page.show');
    Route::livewire('/checkout/{checkoutId}', CheckoutShow::class)->name('checkout.show');
    Route::livewire('/checkout/{checkoutId}/confirmation', CheckoutConfirmation::class)->name('checkout.confirmation');
    Route::livewire('/account/login', CustomerLogin::class)->middleware('throttle:login')->name('account.login');
    Route::livewire('/account/register', CustomerRegister::class)->name('account.register');
    Route::livewire('/forgot-password', CustomerForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', CustomerResetPassword::class)->name('password.reset');
    Route::post('/account/logout', function (): \Illuminate\Http\RedirectResponse {
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('account.logout');

    Route::middleware('auth:customer')->group(function (): void {
        Route::livewire('/account', AccountDashboard::class)->name('account.dashboard');
        Route::livewire('/account/orders', AccountOrders::class)->name('account.orders');
        Route::livewire('/account/orders/{orderNumber}', AccountOrderShow::class)->name('account.order.show');
        Route::livewire('/account/addresses', AccountAddresses::class)->name('account.addresses');
    });
});

Route::livewire('/admin/login', AdminLogin::class)->middleware('throttle:login')->name('admin.login');
Route::livewire('/admin/forgot-password', AdminForgotPassword::class)->middleware('throttle:login')->name('admin.password.request');
Route::livewire('/admin/reset-password/{token}', AdminResetPassword::class)->name('admin.password.reset');
Route::post('/admin/logout', function (): \Illuminate\Http\RedirectResponse {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('admin.login');
})->name('admin.logout');

Route::prefix('admin')->middleware(['auth', 'verified', 'store.resolve', 'role.check:owner,admin,staff,support'])->group(function (): void {
    Route::livewire('/', AdminDashboard::class)->name('admin.dashboard');
    Route::livewire('/products', AdminProductsIndex::class)->name('admin.products.index');
    Route::livewire('/products/create', AdminProductForm::class)->middleware('role.check:owner,admin,staff')->name('admin.products.create');
    Route::livewire('/products/{product}/edit', AdminProductForm::class)->middleware('role.check:owner,admin,staff')->name('admin.products.edit');
    Route::livewire('/orders', AdminOrdersIndex::class)->name('admin.orders.index');
    Route::livewire('/orders/{order}', AdminOrdersShow::class)->name('admin.orders.show');
    Route::livewire('/customers', AdminCustomersIndex::class)->name('admin.customers.index');
    Route::livewire('/customers/{customer}', AdminCustomersShow::class)->name('admin.customers.show');
    Route::livewire('/discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
    Route::livewire('/discounts/create', AdminDiscountForm::class)->middleware('role.check:owner,admin,staff')->name('admin.discounts.create');
    Route::livewire('/discounts/{discount}/edit', AdminDiscountForm::class)->middleware('role.check:owner,admin,staff')->name('admin.discounts.edit');
    Route::livewire('/settings', AdminSettingsGeneral::class)->middleware('role.check:owner,admin')->name('admin.settings');
    Route::livewire('/settings/shipping', AdminSettingsShipping::class)->middleware('role.check:owner,admin')->name('admin.settings.shipping');
    Route::livewire('/settings/taxes', AdminSettingsTaxes::class)->middleware('role.check:owner,admin')->name('admin.settings.taxes');
    Route::livewire('/inventory', AdminInventoryIndex::class)->name('admin.inventory');
    Route::livewire('/collections', AdminCollectionsIndex::class)->name('admin.collections');
    Route::livewire('/collections/create', AdminCollectionsCreate::class)->middleware('role.check:owner,admin,staff')->name('admin.collections.create');
    Route::livewire('/collections/{collection}/edit', AdminCollectionsEdit::class)->middleware('role.check:owner,admin,staff')->name('admin.collections.edit');
    Route::livewire('/themes', AdminThemesIndex::class)->name('admin.themes');
    Route::livewire('/themes/{theme}/editor', AdminThemesEditor::class)->name('admin.themes.editor');
    Route::livewire('/pages', AdminPagesIndex::class)->name('admin.pages');
    Route::livewire('/pages/create', AdminPagesCreate::class)->middleware('role.check:owner,admin')->name('admin.pages.create');
    Route::livewire('/pages/{page}/edit', AdminPagesEdit::class)->middleware('role.check:owner,admin')->name('admin.pages.edit');
    Route::livewire('/navigation', AdminNavigationIndex::class)->name('admin.navigation');
    Route::livewire('/apps', AdminAppsIndex::class)->name('admin.apps');
    Route::livewire('/apps/{installation}', AdminAppsShow::class)->name('admin.apps.show');
    Route::livewire('/developers', AdminDevelopersIndex::class)->name('admin.developers');
    Route::livewire('/analytics', AdminAnalyticsIndex::class)->name('admin.analytics');
    Route::livewire('/search/settings', AdminSearchSettings::class)->name('admin.search.settings');
});
