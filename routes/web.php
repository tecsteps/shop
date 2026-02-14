<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController as AdminForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController as AdminResetPasswordController;
use App\Http\Controllers\Storefront\Account\AddressController as AccountAddressController;
use App\Http\Controllers\Storefront\Account\AuthController as AccountAuthController;
use App\Http\Controllers\Storefront\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Storefront\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Storefront\CartController as StorefrontCartController;
use App\Http\Controllers\Storefront\CheckoutController as StorefrontCheckoutController;
use App\Http\Controllers\Storefront\CollectionController as StorefrontCollectionController;
use App\Http\Controllers\Storefront\HomeController as StorefrontHomeController;
use App\Http\Controllers\Storefront\PageController as StorefrontPageController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Storefront\SearchController as StorefrontSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.auth.')
    ->middleware('store.resolve')
    ->group(function (): void {
        Route::middleware('guest')->group(function (): void {
            Route::get('login', [AdminLoginController::class, 'create'])->name('login');
            Route::post('login', [AdminLoginController::class, 'store'])
                ->middleware('throttle:login')
                ->name('login.store');

            Route::get('forgot-password', [AdminForgotPasswordController::class, 'create'])->name('forgot-password');
            Route::post('forgot-password', [AdminForgotPasswordController::class, 'store'])->name('forgot-password.store');

            Route::get('reset-password/{token}', [AdminResetPasswordController::class, 'create'])->name('reset-password');
            Route::post('reset-password', [AdminResetPasswordController::class, 'store'])->name('reset-password.store');
        });

        Route::post('logout', [AdminLoginController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');
    });

Route::middleware(['auth', 'verified', 'store.resolve', 'role.check'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminPageController::class, 'dashboard'])->name('dashboard');

        Route::prefix('products')->name('products.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'productsIndex'])->name('index');
            Route::get('create', [AdminPageController::class, 'productsCreate'])->name('create');
            Route::post('/', [AdminPageController::class, 'productsStore'])->name('store');
            Route::get('{product}/edit', [AdminPageController::class, 'productsEdit'])->name('edit');
            Route::put('{product}', [AdminPageController::class, 'productsUpdate'])->name('update');
            Route::delete('{product}', [AdminPageController::class, 'productsDestroy'])->name('destroy');
        });

        Route::get('inventory', [AdminPageController::class, 'inventoryIndex'])->name('inventory.index');

        Route::prefix('collections')->name('collections.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'collectionsIndex'])->name('index');
            Route::get('create', [AdminPageController::class, 'collectionsCreate'])->name('create');
            Route::post('/', [AdminPageController::class, 'collectionsStore'])->name('store');
            Route::get('{collection}/edit', [AdminPageController::class, 'collectionsEdit'])->name('edit');
            Route::put('{collection}', [AdminPageController::class, 'collectionsUpdate'])->name('update');
            Route::delete('{collection}', [AdminPageController::class, 'collectionsDestroy'])->name('destroy');
        });

        Route::prefix('orders')->name('orders.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'ordersIndex'])->name('index');
            Route::get('{order}', [AdminPageController::class, 'ordersShow'])->name('show');
        });

        Route::prefix('customers')->name('customers.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'customersIndex'])->name('index');
            Route::get('{customer}', [AdminPageController::class, 'customersShow'])->name('show');
        });

        Route::prefix('discounts')->name('discounts.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'discountsIndex'])->name('index');
            Route::get('create', [AdminPageController::class, 'discountsCreate'])->name('create');
            Route::post('/', [AdminPageController::class, 'discountsStore'])->name('store');
            Route::get('{discount}/edit', [AdminPageController::class, 'discountsEdit'])->name('edit');
            Route::put('{discount}', [AdminPageController::class, 'discountsUpdate'])->name('update');
            Route::delete('{discount}', [AdminPageController::class, 'discountsDestroy'])->name('destroy');
        });

        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'settingsIndex'])->name('index');
            Route::get('shipping', [AdminPageController::class, 'settingsShipping'])->name('shipping');
            Route::get('taxes', [AdminPageController::class, 'settingsTaxes'])->name('taxes');
        });

        Route::prefix('themes')->name('themes.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'themesIndex'])->name('index');
            Route::get('{theme}/editor', [AdminPageController::class, 'themesEditor'])->name('editor');
        });

        Route::prefix('pages')->name('pages.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'pagesIndex'])->name('index');
            Route::get('create', [AdminPageController::class, 'pagesCreate'])->name('create');
            Route::post('/', [AdminPageController::class, 'pagesStore'])->name('store');
            Route::get('{page}/edit', [AdminPageController::class, 'pagesEdit'])->name('edit');
            Route::put('{page}', [AdminPageController::class, 'pagesUpdate'])->name('update');
            Route::delete('{page}', [AdminPageController::class, 'pagesDestroy'])->name('destroy');
        });

        Route::get('navigation', [AdminPageController::class, 'navigationIndex'])->name('navigation.index');

        Route::prefix('apps')->name('apps.')->group(function (): void {
            Route::get('/', [AdminPageController::class, 'appsIndex'])->name('index');
            Route::get('{installation}', [AdminPageController::class, 'appsShow'])->name('show');
        });

        Route::get('developers', [AdminPageController::class, 'developersIndex'])->name('developers.index');
        Route::get('analytics', [AdminPageController::class, 'analyticsIndex'])->name('analytics.index');
        Route::get('search/settings', [AdminPageController::class, 'searchSettings'])->name('search.settings');
    });

Route::middleware('store.resolve')->group(function (): void {
    Route::get('/', [StorefrontHomeController::class, 'index'])->name('home');
    Route::get('/collections', [StorefrontCollectionController::class, 'index'])->name('storefront.collections.index');
    Route::get('/collections/{handle}', [StorefrontCollectionController::class, 'show'])->name('storefront.collections.show');
    Route::get('/products/{handle}', [StorefrontProductController::class, 'show'])->name('storefront.products.show');
    Route::get('/cart', [StorefrontCartController::class, 'show'])->name('storefront.cart.show');
    Route::post('/cart/lines', [StorefrontCartController::class, 'addLine'])->name('storefront.cart.lines.store');
    Route::patch('/cart/lines/{lineId}', [StorefrontCartController::class, 'updateLine'])
        ->whereNumber('lineId')
        ->name('storefront.cart.lines.update');
    Route::delete('/cart/lines/{lineId}', [StorefrontCartController::class, 'removeLine'])
        ->whereNumber('lineId')
        ->name('storefront.cart.lines.destroy');
    Route::post('/cart/checkout', [StorefrontCartController::class, 'startCheckout'])->name('storefront.cart.checkout.start');
    Route::get('/search', [StorefrontSearchController::class, 'index'])->name('storefront.search.index');
    Route::get('/pages/{handle}', [StorefrontPageController::class, 'show'])->name('storefront.pages.show');
    Route::get('/checkout/{checkoutId}', [StorefrontCheckoutController::class, 'show'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.show');
    Route::put('/checkout/{checkoutId}/address', [StorefrontCheckoutController::class, 'updateAddress'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.address.update');
    Route::put('/checkout/{checkoutId}/shipping-method', [StorefrontCheckoutController::class, 'selectShippingMethod'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.shipping-method.update');
    Route::put('/checkout/{checkoutId}/payment-method', [StorefrontCheckoutController::class, 'selectPaymentMethod'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.payment-method.update');
    Route::post('/checkout/{checkoutId}/discount', [StorefrontCheckoutController::class, 'applyDiscount'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.discount.apply');
    Route::delete('/checkout/{checkoutId}/discount', [StorefrontCheckoutController::class, 'removeDiscount'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.discount.remove');
    Route::post('/checkout/{checkoutId}/pay', [StorefrontCheckoutController::class, 'pay'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.pay');
    Route::get('/checkout/{checkoutId}/confirmation', [StorefrontCheckoutController::class, 'confirmation'])
        ->whereNumber('checkoutId')
        ->name('storefront.checkout.confirmation');

    Route::prefix('account')
        ->name('account.')
        ->group(function (): void {
            Route::get('login', [AccountAuthController::class, 'showLogin'])->name('login');
            Route::post('login', [AccountAuthController::class, 'login'])
                ->middleware('throttle:login')
                ->name('login.store');
            Route::get('register', [AccountAuthController::class, 'showRegister'])->name('register');
            Route::post('register', [AccountAuthController::class, 'register'])
                ->middleware('throttle:login')
                ->name('register.store');
            Route::get('forgot-password', [AccountAuthController::class, 'showForgotPassword'])->name('forgot-password');
            Route::post('forgot-password', [AccountAuthController::class, 'sendResetLink'])->name('forgot-password.store');
            Route::get('reset-password/{token}', [AccountAuthController::class, 'showResetPassword'])->name('reset-password');
            Route::post('reset-password', [AccountAuthController::class, 'resetPassword'])->name('reset-password.store');

            Route::middleware('auth:customer')->group(function (): void {
                Route::get('/', [AccountDashboardController::class, 'index'])->name('dashboard');
                Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
                Route::get('/orders/{orderNumber}', [AccountOrderController::class, 'show'])->name('orders.show');
                Route::get('/addresses', [AccountAddressController::class, 'index'])->name('addresses.index');
                Route::post('/addresses', [AccountAddressController::class, 'store'])->name('addresses.store');
                Route::put('/addresses/{address}', [AccountAddressController::class, 'update'])
                    ->whereNumber('address')
                    ->name('addresses.update');
                Route::delete('/addresses/{address}', [AccountAddressController::class, 'destroy'])
                    ->whereNumber('address')
                    ->name('addresses.destroy');
                Route::post('/logout', [AccountAuthController::class, 'logout'])->name('logout');
            });
        });
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
