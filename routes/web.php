<?php

use App\Livewire\Admin\Analytics\Index as AdminAnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AdminAppsIndex;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Collections\Form as AdminCollectionsForm;
use App\Livewire\Admin\Collections\Index as AdminCollectionsIndex;
use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomersShow;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Developers\Index as AdminDevelopersIndex;
use App\Livewire\Admin\Discounts\Form as AdminDiscountsForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Livewire\Admin\Navigation\Index as AdminNavigationIndex;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Pages\Form as AdminPagesForm;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Products\Form as AdminProductsForm;
use App\Livewire\Admin\Products\Index as AdminProductsIndex;
use App\Livewire\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Admin\Settings\Shipping as AdminSettingsShipping;
use App\Livewire\Admin\Settings\Taxes as AdminSettingsTaxes;
use App\Livewire\Admin\Themes\Editor as AdminThemesEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Livewire\Storefront\Account\Dashboard as AccountDashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Confirmation as CheckoutConfirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionsShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Pages\Show as PagesShow;
use App\Livewire\Storefront\Products\Show as ProductsShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin auth routes
Route::prefix('admin')->group(function () {
    Route::get('login', AdminLogin::class)
        ->middleware('guest')
        ->name('admin.login');

    Route::post('logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');

        Route::get('/products', AdminProductsIndex::class)->name('admin.products.index');
        Route::get('/products/create', AdminProductsForm::class)->name('admin.products.create');
        Route::get('/products/{product}/edit', AdminProductsForm::class)->name('admin.products.edit');

        Route::get('/orders', AdminOrdersIndex::class)->name('admin.orders.index');
        Route::get('/orders/{order}', AdminOrdersShow::class)->name('admin.orders.show');

        Route::get('/collections', AdminCollectionsIndex::class)->name('admin.collections.index');
        Route::get('/collections/create', AdminCollectionsForm::class)->name('admin.collections.create');
        Route::get('/collections/{collection}/edit', AdminCollectionsForm::class)->name('admin.collections.edit');

        Route::get('/customers', AdminCustomersIndex::class)->name('admin.customers.index');
        Route::get('/customers/{customer}', AdminCustomersShow::class)->name('admin.customers.show');

        Route::get('/discounts', AdminDiscountsIndex::class)->name('admin.discounts.index');
        Route::get('/discounts/create', AdminDiscountsForm::class)->name('admin.discounts.create');
        Route::get('/discounts/{discount}/edit', AdminDiscountsForm::class)->name('admin.discounts.edit');

        Route::get('/settings', AdminSettingsIndex::class)->name('admin.settings.index');
        Route::get('/settings/shipping', AdminSettingsShipping::class)->name('admin.settings.shipping');
        Route::get('/settings/taxes', AdminSettingsTaxes::class)->name('admin.settings.taxes');

        Route::get('/pages', AdminPagesIndex::class)->name('admin.pages.index');
        Route::get('/pages/create', AdminPagesForm::class)->name('admin.pages.create');
        Route::get('/pages/{page}/edit', AdminPagesForm::class)->name('admin.pages.edit');

        Route::get('/themes', AdminThemesIndex::class)->name('admin.themes.index');
        Route::get('/themes/{theme}/editor', AdminThemesEditor::class)->name('admin.themes.editor');

        Route::get('/navigation', AdminNavigationIndex::class)->name('admin.navigation.index');

        Route::get('/analytics', AdminAnalyticsIndex::class)->name('admin.analytics.index');

        Route::get('/apps', AdminAppsIndex::class)->name('admin.apps.index');
        Route::get('/developers', AdminDevelopersIndex::class)->name('admin.developers.index');
    });
});

// Storefront routes
Route::middleware(['storefront'])->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/collections', CollectionsIndex::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', CollectionsShow::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', ProductsShow::class)->name('storefront.products.show');
    Route::get('/cart', CartShow::class)->name('storefront.cart');
    Route::get('/checkout', CheckoutShow::class)->name('storefront.checkout');
    Route::get('/checkout/confirmation', CheckoutConfirmation::class)->name('storefront.checkout.confirmation');
    Route::get('/search', SearchIndex::class)->name('storefront.search');

    Route::get('/cart-count', function () {
        $cartId = session('cart_id');
        $count = $cartId
            ? \App\Models\CartLine::where('cart_id', $cartId)->sum('quantity')
            : 0;

        return response()->json(['count' => $count]);
    })->name('storefront.cart.count');
    Route::get('/pages/{handle}', PagesShow::class)->name('storefront.pages.show');

    Route::get('account/login', CustomerLogin::class)->name('storefront.login');
    Route::get('account/register', CustomerRegister::class)->name('storefront.register');

    Route::post('account/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.login');
    })->name('storefront.logout');

    Route::middleware(['auth:customer'])->group(function () {
        Route::get('account', AccountDashboard::class)->name('storefront.account');
        Route::get('account/orders', OrdersIndex::class)->name('storefront.account.orders');
        Route::get('account/orders/{orderNumber}', OrdersShow::class)->name('storefront.account.orders.show');
        Route::get('account/addresses', AddressesIndex::class)->name('storefront.account.addresses');
    });
});

require __DIR__.'/settings.php';
