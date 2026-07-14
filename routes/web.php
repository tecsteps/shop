<?php

use App\Livewire\Admin\Analytics\Index as AdminAnalytics;
use App\Livewire\Admin\Apps\Index as AdminApps;
use App\Livewire\Admin\Apps\Show as AdminApp;
use App\Livewire\Admin\Auth\ForgotPassword as AdminForgotPassword;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Auth\ResetPassword as AdminResetPassword;
use App\Livewire\Admin\Collections\Form as AdminCollectionForm;
use App\Livewire\Admin\Collections\Index as AdminCollections;
use App\Livewire\Admin\Customers\Index as AdminCustomers;
use App\Livewire\Admin\Customers\Show as AdminCustomer;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopers;
use App\Livewire\Admin\Discounts\Form as AdminDiscountForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscounts;
use App\Livewire\Admin\Inventory\Index as AdminInventory;
use App\Livewire\Admin\Navigation\Index as AdminNavigation;
use App\Livewire\Admin\Orders\Index as AdminOrders;
use App\Livewire\Admin\Orders\Show as AdminOrder;
use App\Livewire\Admin\Pages\Form as AdminPageForm;
use App\Livewire\Admin\Pages\Index as AdminPages;
use App\Livewire\Admin\Products\Form as AdminProductForm;
use App\Livewire\Admin\Products\Index as AdminProducts;
use App\Livewire\Admin\Search\Settings as AdminSearchSettings;
use App\Livewire\Admin\Settings\Checkout as AdminCheckoutSettings;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Settings\Notifications as AdminNotificationSettings;
use App\Livewire\Admin\Settings\Shipping as AdminShipping;
use App\Livewire\Admin\Settings\Taxes as AdminTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemeEditor;
use App\Livewire\Admin\Themes\Index as AdminThemes;
use App\Livewire\Storefront\Account\Addresses\Index as AccountAddresses;
use App\Livewire\Storefront\Account\Auth\ForgotPassword as CustomerForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Auth\ResetPassword as CustomerResetPassword;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrders;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrder;
use App\Livewire\Storefront\Cart\Show as Cart;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show as Checkout;
use App\Livewire\Storefront\Collections\Index as Collections;
use App\Livewire\Storefront\Collections\Show as Collection;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as Page;
use App\Livewire\Storefront\Products\Show as Product;
use App\Livewire\Storefront\Search\Index as Search;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/oauth/authorize', fn () => response()->json([
    'message' => 'The OAuth app ecosystem is not implemented in this release.',
], 501));
Route::post('/oauth/token', fn () => response()->json([
    'message' => 'The OAuth app ecosystem is not implemented in this release.',
], 501))->withoutMiddleware(ValidateCsrfToken::class);

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', AdminLogin::class)->name('login');
    Route::get('/forgot-password', AdminForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', AdminResetPassword::class)->name('password.reset');

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
        ]);
    })->middleware('auth')->name('logout');

    Route::middleware(['auth', 'verified', 'admin.store', 'role.check:owner,admin,staff,support'])->group(function (): void {
        Route::get('/', AdminDashboard::class)->name('dashboard');
        Route::get('/products', AdminProducts::class)->name('products.index');
        Route::get('/products/create', AdminProductForm::class)->name('products.create');
        Route::get('/products/{product}/edit', AdminProductForm::class)->name('products.edit');
        Route::get('/inventory', AdminInventory::class)->name('inventory.index');
        Route::get('/collections', AdminCollections::class)->name('collections.index');
        Route::get('/collections/create', AdminCollectionForm::class)->name('collections.create');
        Route::get('/collections/{collection}/edit', AdminCollectionForm::class)->name('collections.edit');
        Route::get('/orders', AdminOrders::class)->name('orders.index');
        Route::get('/orders/{order}', AdminOrder::class)->name('orders.show');
        Route::get('/customers', AdminCustomers::class)->name('customers.index');
        Route::get('/customers/{customer}', AdminCustomer::class)->name('customers.show');
        Route::get('/discounts', AdminDiscounts::class)->name('discounts.index');
        Route::get('/discounts/create', AdminDiscountForm::class)->name('discounts.create');
        Route::get('/discounts/{discount}/edit', AdminDiscountForm::class)->name('discounts.edit');
        Route::get('/settings', AdminSettings::class)->name('settings.index');
        Route::get('/settings/shipping', AdminShipping::class)->name('settings.shipping');
        Route::get('/settings/taxes', AdminTaxes::class)->name('settings.taxes');
        Route::get('/settings/checkout', AdminCheckoutSettings::class)->name('settings.checkout');
        Route::get('/settings/notifications', AdminNotificationSettings::class)->name('settings.notifications');
        Route::get('/themes', AdminThemes::class)->name('themes.index');
        Route::get('/themes/{theme}/editor', AdminThemeEditor::class)->name('themes.editor');
        Route::get('/pages', AdminPages::class)->name('pages.index');
        Route::get('/pages/create', AdminPageForm::class)->name('pages.create');
        Route::get('/pages/{page}/edit', AdminPageForm::class)->name('pages.edit');
        Route::get('/navigation', AdminNavigation::class)->name('navigation.index');
        Route::get('/apps', AdminApps::class)->name('apps.index');
        Route::get('/apps/{installation}', AdminApp::class)->name('apps.show');
        Route::get('/developers', AdminDevelopers::class)->name('developers.index');
        Route::get('/analytics', AdminAnalytics::class)->name('analytics.index');
        Route::get('/search/settings', AdminSearchSettings::class)->name('search.settings');
    });
});

Route::middleware('storefront')->group(function (): void {
    Route::get('/', Home::class)->name('storefront.home');
    Route::get('/collections', Collections::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', Collection::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', Product::class)->name('storefront.products.show');
    Route::get('/cart', Cart::class)->name('storefront.cart');
    Route::get('/search', Search::class)->name('storefront.search');
    Route::get('/pages/{handle}', Page::class)->name('storefront.pages.show');

    Route::get('/account/login', CustomerLogin::class)->name('customer.login');
    Route::get('/account/register', CustomerRegister::class)->name('customer.register');
    Route::get('/forgot-password', CustomerForgotPassword::class)->name('customer.password.request');
    Route::get('/reset-password/{token}', CustomerResetPassword::class)->name('customer.password.reset');

    Route::middleware('customer.auth')->group(function (): void {
        Route::get('/account', AccountDashboard::class)->name('customer.dashboard');
        Route::get('/account/orders', AccountOrders::class)->name('customer.orders.index');
        Route::get('/account/orders/{orderNumber}', AccountOrder::class)->name('customer.orders.show');
        Route::get('/account/addresses', AccountAddresses::class)->name('customer.addresses');
        Route::post('/account/logout', function () {
            Auth::guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/account/login');
        })->name('customer.logout');
    });

    Route::get('/checkout/{checkoutId}', Checkout::class)->middleware('throttle:checkout')->name('storefront.checkout');
    Route::get('/checkout/{checkoutId}/confirmation', Confirmation::class)->name('storefront.checkout.confirmation');
});

Route::redirect('/home', '/')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
