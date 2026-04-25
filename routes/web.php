<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('storefront')->group(function (): void {
    Route::get('/', [StorefrontController::class, 'home'])->name('home');
    Route::get('/collections', [StorefrontController::class, 'collections'])->name('collections.index');
    Route::get('/collections/{handle}', [StorefrontController::class, 'collection'])->name('collections.show');
    Route::get('/products/{handle}', [StorefrontController::class, 'product'])->name('products.show');
    Route::get('/search', [StorefrontController::class, 'search'])->middleware('throttle:search')->name('search');
    Route::get('/pages/{handle}', [StorefrontController::class, 'page'])->name('pages.show');

    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/lines', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/lines/{line}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/discount', [CartController::class, 'discount'])->name('cart.discount');
    Route::delete('/cart/discount', [CartController::class, 'removeDiscount'])->name('cart.discount.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->middleware('throttle:checkout')->name('cart.checkout');

    Route::get('/checkout/{checkout}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{checkout}', [CheckoutController::class, 'update'])->middleware('throttle:checkout')->name('checkout.update');
    Route::get('/checkout/confirmation/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

    Route::get('/account/login', [CustomerAccountController::class, 'login'])->name('account.login');
    Route::post('/account/login', [CustomerAccountController::class, 'authenticate'])->middleware('throttle:login')->name('account.authenticate');
    Route::get('/account/register', [CustomerAccountController::class, 'register'])->name('account.register');
    Route::post('/account/register', [CustomerAccountController::class, 'store'])->name('account.store');
    Route::post('/account/logout', [CustomerAccountController::class, 'logout'])->name('account.logout');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/account', [CustomerAccountController::class, 'dashboard'])->name('account.dashboard');
        Route::get('/account/orders', [CustomerAccountController::class, 'orders'])->name('account.orders');
        Route::get('/account/orders/{orderNumber}', [CustomerAccountController::class, 'order'])->name('account.orders.show');
        Route::get('/account/addresses', [CustomerAccountController::class, 'addresses'])->name('account.addresses');
        Route::post('/account/addresses', [CustomerAccountController::class, 'saveAddress'])->name('account.addresses.save');
    });
});

require __DIR__.'/settings.php';

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'login'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'authenticate'])->middleware('throttle:login')->name('authenticate');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/products', [AdminController::class, 'products'])->name('products.index');
        Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
        Route::patch('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
        Route::patch('/products/{product}/archive', [AdminController::class, 'archiveProduct'])->name('products.archive');

        Route::get('/orders', [AdminController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{order}', [AdminController::class, 'order'])->name('orders.show');
        Route::post('/orders/{order}/confirm-payment', [AdminController::class, 'confirmPayment'])->name('orders.confirm-payment');
        Route::post('/orders/{order}/refunds', [AdminController::class, 'refund'])->name('orders.refunds');
        Route::post('/orders/{order}/fulfillments', [AdminController::class, 'fulfill'])->name('orders.fulfillments');
        Route::patch('/fulfillments/{fulfillment}/ship', [AdminController::class, 'markShipped'])->name('fulfillments.ship');
        Route::patch('/fulfillments/{fulfillment}/deliver', [AdminController::class, 'markDelivered'])->name('fulfillments.deliver');

        Route::get('/customers', [AdminController::class, 'customers'])->name('customers.index');
        Route::get('/customers/{customer}', [AdminController::class, 'customer'])->name('customers.show');

        Route::get('/discounts', [AdminController::class, 'discounts'])->name('discounts.index');
        Route::post('/discounts', [AdminController::class, 'storeDiscount'])->name('discounts.store');

        Route::get('/settings', [AdminController::class, 'settings'])->name('settings.index');
        Route::patch('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/shipping-rates', [AdminController::class, 'addShippingRate'])->name('settings.shipping-rates.store');
        Route::patch('/settings/taxes', [AdminController::class, 'toggleTax'])->name('settings.taxes.toggle');

        Route::get('/collections', fn () => app(AdminController::class)->simple('collections'))->name('collections.index');
        Route::get('/inventory', fn () => app(AdminController::class)->simple('inventory'))->name('inventory.index');
        Route::get('/pages', fn () => app(AdminController::class)->simple('pages'))->name('pages.index');
        Route::get('/navigation', fn () => app(AdminController::class)->simple('navigation'))->name('navigation.index');
        Route::get('/themes', fn () => app(AdminController::class)->simple('themes'))->name('themes.index');
        Route::get('/analytics', fn () => app(AdminController::class)->simple('analytics'))->name('analytics.index');
        Route::get('/apps', fn () => app(AdminController::class)->simple('apps'))->name('apps.index');
        Route::get('/developers', fn () => app(AdminController::class)->simple('developers'))->name('developers.index');
        Route::get('/search/settings', fn () => app(AdminController::class)->simple('search settings'))->name('search.settings');
    });
});
