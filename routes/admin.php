<?php

use App\Http\Controllers\Admin\Auth\EmailVerificationController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Collections;
use App\Livewire\Admin\Customers;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Discounts;
use App\Livewire\Admin\Inventory;
use App\Livewire\Admin\Navigation;
use App\Livewire\Admin\Orders;
use App\Livewire\Admin\Pages;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Search;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Themes;
use Illuminate\Support\Facades\Route;

// Admin panel routes. Loaded inside the "web" middleware group (see bootstrap/app.php).
Route::prefix('admin')->name('admin.')->group(function (): void {
    // Auth pages (spec 02 §1.1). Submissions are handled by Livewire actions.
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::post('/logout', LogoutController::class)->name('logout');

    Route::middleware(['auth', 'verified', 'store.resolve:admin', 'role.check:owner,admin,staff,support'])->group(function (): void {
        Route::livewire('/', Dashboard::class)->name('dashboard');

        Route::livewire('/products', Products\Index::class)->name('products.index');
        Route::livewire('/products/create', Products\Form::class)->name('products.create');
        Route::livewire('/products/{product}/edit', Products\Form::class)->name('products.edit');

        Route::livewire('/collections', Collections\Index::class)->name('collections.index');
        Route::livewire('/collections/create', Collections\Form::class)->name('collections.create');
        Route::livewire('/collections/{collection}/edit', Collections\Form::class)->name('collections.edit');

        Route::livewire('/inventory', Inventory\Index::class)->name('inventory.index');

        Route::livewire('/orders', Orders\Index::class)->name('orders.index');
        Route::livewire('/orders/{order}', Orders\Show::class)->name('orders.show');

        Route::livewire('/customers', Customers\Index::class)->name('customers.index');
        Route::livewire('/customers/{customer}', Customers\Show::class)->name('customers.show');

        Route::livewire('/discounts', Discounts\Index::class)->name('discounts.index');
        Route::livewire('/discounts/create', Discounts\Form::class)->name('discounts.create');
        Route::livewire('/discounts/{discount}/edit', Discounts\Form::class)->name('discounts.edit');

        Route::livewire('/pages', Pages\Index::class)->name('pages.index');
        Route::livewire('/pages/create', Pages\Form::class)->name('pages.create');
        Route::livewire('/pages/{page}/edit', Pages\Form::class)->name('pages.edit');

        Route::livewire('/navigation', Navigation\Index::class)->name('navigation.index');

        Route::livewire('/themes', Themes\Index::class)->name('themes.index');
        Route::livewire('/themes/{theme}/editor', Themes\Editor::class)->name('themes.editor');

        Route::livewire('/settings', Settings\Index::class)->name('settings.index');
        Route::livewire('/settings/shipping', Settings\Shipping::class)->name('settings.shipping');
        Route::livewire('/settings/taxes', Settings\Taxes::class)->name('settings.taxes');

        Route::livewire('/search/settings', Search\Settings::class)->name('search.settings');

        Route::livewire('/analytics', Analytics\Index::class)->name('analytics.index');
    });
});

// Email verification for admin users. Route names are fixed by Laravel's
// "verified" middleware and MustVerifyEmail notification.
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
