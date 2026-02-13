<?php

use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Collections;
use App\Livewire\Admin\Customers;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Discounts;
use App\Livewire\Admin\Inventory;
use App\Livewire\Admin\Navigation;
use App\Livewire\Admin\Orders;
use App\Livewire\Admin\Pages;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Themes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Admin auth routes (no auth required)
Route::get('/admin/login', Login::class)->name('admin.login');

// Admin logout
Route::get('/admin/logout', function () {
    Auth::guard('web')->logout();
    Session::invalidate();
    Session::regenerateToken();

    return redirect()->route('admin.login');
})->middleware('auth')->name('admin.logout');

// Admin protected routes
Route::prefix('admin')->middleware(['auth', 'store.resolve'])->group(function () {
    Route::get('/', Dashboard::class)->name('admin.dashboard');

    // Products
    Route::get('/products', Products\Index::class)->name('admin.products.index');
    Route::get('/products/create', Products\Form::class)->name('admin.products.create');
    Route::get('/products/{product}/edit', Products\Form::class)->name('admin.products.edit');

    // Orders
    Route::get('/orders', Orders\Index::class)->name('admin.orders.index');
    Route::get('/orders/{order}', Orders\Show::class)->name('admin.orders.show');

    // Collections
    Route::get('/collections', Collections\Index::class)->name('admin.collections.index');
    Route::get('/collections/create', Collections\Form::class)->name('admin.collections.create');
    Route::get('/collections/{collection}/edit', Collections\Form::class)->name('admin.collections.edit');

    // Customers
    Route::get('/customers', Customers\Index::class)->name('admin.customers.index');
    Route::get('/customers/{customer}', Customers\Show::class)->name('admin.customers.show');

    // Discounts
    Route::get('/discounts', Discounts\Index::class)->name('admin.discounts.index');
    Route::get('/discounts/create', Discounts\Form::class)->name('admin.discounts.create');
    Route::get('/discounts/{discount}/edit', Discounts\Form::class)->name('admin.discounts.edit');

    // Pages
    Route::get('/pages', Pages\Index::class)->name('admin.pages.index');
    Route::get('/pages/create', Pages\Form::class)->name('admin.pages.create');
    Route::get('/pages/{page}/edit', Pages\Form::class)->name('admin.pages.edit');

    // Settings
    Route::get('/settings', Settings\Index::class)->name('admin.settings.index');

    // Inventory
    Route::get('/inventory', Inventory\Index::class)->name('admin.inventory.index');

    // Navigation
    Route::get('/navigation', Navigation\Index::class)->name('admin.navigation.index');

    // Themes
    Route::get('/themes', Themes\Index::class)->name('admin.themes.index');

    // Analytics
    Route::get('/analytics', Analytics\Index::class)->name('admin.analytics.index');
});
