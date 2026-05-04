<?php

use App\Http\Middleware\EnsureUserEmailIsVerified;
use App\Livewire\Admin\Analytics\Index as AdminAnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AdminAppsIndex;
use App\Livewire\Admin\Apps\Show as AdminAppShow;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as AdminCollectionForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomerShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopersIndex;
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
use App\Livewire\Admin\Search\Settings as AdminSearchSettings;
use App\Livewire\Admin\Settings\Checkout as AdminSettingsCheckout;
use App\Livewire\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Admin\Settings\Notifications as AdminSettingsNotifications;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemeEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as CustomerForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as CustomerResetPassword;
use App\Livewire\Storefront\Account\Orders\Index as CustomerOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as CustomerOrderShow;
use App\Livewire\Storefront\Cart\Show as StorefrontCartShow;
use App\Livewire\Storefront\Checkout\Confirmation as StorefrontCheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as StorefrontCheckoutShow;
use App\Livewire\Storefront\Collections\Index as StorefrontCollectionsIndex;
use App\Livewire\Storefront\Collections\Show as StorefrontCollectionShow;
use App\Livewire\Storefront\Home as StorefrontHome;
use App\Livewire\Storefront\Pages\Show as StorefrontPageShow;
use App\Livewire\Storefront\Products\Show as StorefrontProductShow;
use App\Livewire\Storefront\Search\Index as StorefrontSearchIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['storefront'])->group(function (): void {
    Route::livewire('/', StorefrontHome::class)->name('home');
    Route::livewire('collections', StorefrontCollectionsIndex::class)->name('collections.index');
    Route::livewire('collections/{handle}', StorefrontCollectionShow::class)->name('collections.show');
    Route::livewire('products/{handle}', StorefrontProductShow::class)->name('products.show');
    Route::livewire('cart', StorefrontCartShow::class)->name('cart.show');
    Route::livewire('checkout', StorefrontCheckoutShow::class)->name('checkout.show');
    Route::livewire('checkout/confirmation/{order}', StorefrontCheckoutConfirmation::class)->name('checkout.confirmation');
    Route::livewire('search', StorefrontSearchIndex::class)->name('search.index');
    Route::livewire('pages/{handle}', StorefrontPageShow::class)->name('pages.show');
});

Route::livewire('admin/login', AdminLogin::class)
    ->middleware('guest')
    ->name('admin.login');

Route::post('admin/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('admin.login');
})->middleware('auth')->name('admin.logout');

Route::middleware(['auth', EnsureUserEmailIsVerified::class, 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::livewire('/', AdminDashboard::class)->name('dashboard');
    Route::livewire('analytics', AdminAnalyticsIndex::class)->name('analytics.index');
    Route::livewire('apps', AdminAppsIndex::class)->name('apps.index');
    Route::livewire('apps/{installation}', AdminAppShow::class)->name('apps.show');
    Route::livewire('developers', AdminDevelopersIndex::class)->name('developers.index');
    Route::livewire('products', AdminProductsIndex::class)->name('products.index');
    Route::livewire('products/create', AdminProductForm::class)->name('products.create');
    Route::livewire('products/{product}/edit', AdminProductForm::class)->name('products.edit');
    Route::livewire('inventory', AdminInventoryIndex::class)->name('inventory.index');
    Route::livewire('orders', AdminOrdersIndex::class)->name('orders.index');
    Route::livewire('orders/{order}', AdminOrderShow::class)->name('orders.show');
    Route::livewire('customers', AdminCustomersIndex::class)->name('customers.index');
    Route::livewire('customers/{customer}', AdminCustomerShow::class)->name('customers.show');
    Route::livewire('discounts', AdminDiscountsIndex::class)->name('discounts.index');
    Route::livewire('discounts/create', AdminDiscountForm::class)->name('discounts.create');
    Route::livewire('discounts/{discount}/edit', AdminDiscountForm::class)->name('discounts.edit');
    Route::livewire('pages', AdminPagesIndex::class)->name('pages.index');
    Route::livewire('pages/create', AdminPageForm::class)->name('pages.create');
    Route::livewire('pages/{page}/edit', AdminPageForm::class)->name('pages.edit');
    Route::livewire('navigation', AdminNavigationIndex::class)->name('navigation.index');
    Route::livewire('themes', AdminThemesIndex::class)->name('themes.index');
    Route::livewire('themes/{theme}/editor', AdminThemeEditor::class)->name('themes.editor');
    Route::livewire('settings', AdminSettingsIndex::class)->name('settings.index');
    Route::livewire('settings/shipping', AdminSettingsShipping::class)->name('settings.shipping');
    Route::livewire('settings/taxes', AdminSettingsTaxes::class)->name('settings.taxes');
    Route::livewire('settings/checkout', AdminSettingsCheckout::class)->name('settings.checkout');
    Route::livewire('settings/notifications', AdminSettingsNotifications::class)->name('settings.notifications');
    Route::livewire('search/settings', AdminSearchSettings::class)->name('search.settings');
    Route::livewire('collections', AdminCollectionsIndex::class)->name('collections.index');
    Route::livewire('collections/create', AdminCollectionForm::class)->name('collections.create');
    Route::livewire('collections/{collection}/edit', AdminCollectionForm::class)->name('collections.edit');
});

Route::middleware(['storefront'])->group(function (): void {
    Route::livewire('account/login', CustomerLogin::class)
        ->middleware('guest:customer')
        ->name('account.login');

    Route::livewire('account/register', CustomerRegister::class)
        ->middleware('guest:customer')
        ->name('account.register');

    Route::livewire('account/forgot-password', CustomerForgotPassword::class)
        ->middleware('guest:customer')
        ->name('account.password.request');

    Route::livewire('account/reset-password/{token}', CustomerResetPassword::class)
        ->middleware('guest:customer')
        ->name('account.password.reset');

    Route::livewire('account', CustomerOrdersIndex::class)
        ->middleware('auth:customer')
        ->name('account.dashboard');

    Route::livewire('account/orders/{order}', CustomerOrderShow::class)
        ->middleware('auth:customer')
        ->name('account.orders.show');
});

Route::redirect('dashboard', 'admin')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
