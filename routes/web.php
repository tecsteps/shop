<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['storefront'])->group(function () {
    Route::get('/', \App\Livewire\Storefront\Home::class)->name('home');
    Route::get('/collections', \App\Livewire\Storefront\Collections\Index::class)->name('storefront.collections.index');
    Route::get('/collections/{handle}', \App\Livewire\Storefront\Collections\Show::class)->name('storefront.collections.show');
    Route::get('/products/{handle}', \App\Livewire\Storefront\Products\Show::class)->name('storefront.products.show');
    Route::get('/cart', \App\Livewire\Storefront\Cart\Show::class)->name('storefront.cart');
    Route::get('/checkout', \App\Livewire\Storefront\Checkout\Show::class)->name('storefront.checkout');
    Route::get('/checkout/confirmation/{checkout}', \App\Livewire\Storefront\Checkout\Confirmation::class)->name('storefront.checkout.confirmation');
    Route::get('/search', \App\Livewire\Storefront\Search\Index::class)->name('storefront.search');
    Route::get('/pages/{handle}', \App\Livewire\Storefront\Pages\Show::class)->name('storefront.pages.show');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('admin')->group(function () {
    Route::get('login', \App\Livewire\Admin\Auth\Login::class)
        ->name('admin.login');

    Route::post('logout', function () {
        auth()->guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');

        // Products
        Route::get('products', \App\Livewire\Admin\Products\Index::class)->name('admin.products.index');
        Route::get('products/create', \App\Livewire\Admin\Products\Form::class)->name('admin.products.create');
        Route::get('products/{product}/edit', \App\Livewire\Admin\Products\Form::class)->name('admin.products.edit');

        // Collections
        Route::get('collections', \App\Livewire\Admin\Collections\Index::class)->name('admin.collections.index');
        Route::get('collections/create', \App\Livewire\Admin\Collections\Form::class)->name('admin.collections.create');
        Route::get('collections/{collection}/edit', \App\Livewire\Admin\Collections\Form::class)->name('admin.collections.edit');

        // Inventory
        Route::get('inventory', \App\Livewire\Admin\Inventory\Index::class)->name('admin.inventory.index');

        // Orders
        Route::get('orders', \App\Livewire\Admin\Orders\Index::class)->name('admin.orders.index');
        Route::get('orders/{order}', \App\Livewire\Admin\Orders\Show::class)->name('admin.orders.show');

        // Customers
        Route::get('customers', \App\Livewire\Admin\Customers\Index::class)->name('admin.customers.index');
        Route::get('customers/{customer}', \App\Livewire\Admin\Customers\Show::class)->name('admin.customers.show');

        // Discounts
        Route::get('discounts', \App\Livewire\Admin\Discounts\Index::class)->name('admin.discounts.index');
        Route::get('discounts/create', \App\Livewire\Admin\Discounts\Form::class)->name('admin.discounts.create');
        Route::get('discounts/{discount}/edit', \App\Livewire\Admin\Discounts\Form::class)->name('admin.discounts.edit');

        // Content
        Route::get('pages', \App\Livewire\Admin\Pages\Index::class)->name('admin.pages.index');
        Route::get('pages/create', \App\Livewire\Admin\Pages\Form::class)->name('admin.pages.create');
        Route::get('pages/{page}/edit', \App\Livewire\Admin\Pages\Form::class)->name('admin.pages.edit');
        Route::get('navigation', \App\Livewire\Admin\Navigation\Index::class)->name('admin.navigation.index');
        Route::get('themes', \App\Livewire\Admin\Themes\Index::class)->name('admin.themes.index');

        // Analytics
        Route::get('analytics', \App\Livewire\Admin\Analytics\Index::class)->name('admin.analytics.index');

        // Apps
        Route::get('apps', \App\Livewire\Admin\Apps\Index::class)->name('admin.apps.index');

        // Developers
        Route::get('developers', \App\Livewire\Admin\Developers\Index::class)->name('admin.developers.index');

        // Settings
        Route::get('settings', \App\Livewire\Admin\Settings\Index::class)->name('admin.settings.index');
    });
});

Route::prefix('account')->middleware(['storefront'])->group(function () {
    Route::get('login', \App\Livewire\Storefront\Account\Auth\Login::class)
        ->name('storefront.login');

    Route::get('register', \App\Livewire\Storefront\Account\Auth\Register::class)
        ->name('storefront.register');

    Route::get('/', \App\Livewire\Storefront\Account\Dashboard::class)
        ->middleware(['auth.customer'])->name('storefront.account');

    Route::get('orders', \App\Livewire\Storefront\Account\Orders\Index::class)
        ->middleware(['auth.customer'])->name('storefront.account.orders');

    Route::get('orders/{orderNumber}', \App\Livewire\Storefront\Account\Orders\Show::class)
        ->middleware(['auth.customer'])->name('storefront.account.orders.show');

    Route::get('addresses', \App\Livewire\Storefront\Account\Addresses\Index::class)
        ->middleware(['auth.customer'])->name('storefront.account.addresses');

    Route::post('logout', function () {
        auth()->guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('storefront.login');
    })->middleware(['auth.customer'])->name('storefront.logout');
});

require __DIR__.'/settings.php';
