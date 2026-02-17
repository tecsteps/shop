<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Enums\StoreUserRole;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Listeners\DispatchOrderCancelledWebhook;
use App\Listeners\DispatchOrderCreatedWebhook;
use App\Listeners\DispatchOrderFulfilledWebhook;
use App\Listeners\DispatchOrderPaidWebhook;
use App\Listeners\DispatchOrderRefundedWebhook;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Payments\MockPaymentProvider;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeSettingsService::class);
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuth();
        $this->configureRateLimiters();
        $this->configureGates();

        Product::observe(ProductObserver::class);

        Livewire::addPersistentMiddleware([
            \App\Http\Middleware\ResolveStore::class,
            \App\Http\Middleware\CustomerAuthenticate::class,
        ]);

        $this->configureEventListeners();
    }

    /**
     * Register event listeners for webhook dispatching.
     */
    protected function configureEventListeners(): void
    {
        Event::listen(OrderCreated::class, DispatchOrderCreatedWebhook::class);
        Event::listen(OrderPaid::class, DispatchOrderPaidWebhook::class);
        Event::listen(OrderFulfilled::class, DispatchOrderFulfilledWebhook::class);
        Event::listen(OrderCancelled::class, DispatchOrderCancelledWebhook::class);
        Event::listen(OrderRefunded::class, DispatchOrderRefundedWebhook::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Register the custom customer user provider.
     */
    protected function configureAuth(): void
    {
        Auth::provider('customer', function ($app, array $config) {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Configure rate limiters for login and API endpoints.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api.admin', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api.storefront', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)->by($request->session()->getId());
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('analytics', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });
    }

    /**
     * Register authorization gates for non-model operations.
     */
    protected function configureGates(): void
    {
        $ownerOrAdmin = [StoreUserRole::Owner, StoreUserRole::Admin];
        $ownerAdminOrStaff = [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff];

        Gate::define('manage-store-settings', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('manage-staff', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('manage-developers', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('view-analytics', fn ($user) => $this->checkGateRole($user, $ownerAdminOrStaff));
        Gate::define('manage-shipping', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('manage-taxes', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('manage-search-settings', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('manage-navigation', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
        Gate::define('manage-apps', fn ($user) => $this->checkGateRole($user, $ownerOrAdmin));
    }

    /**
     * Check if the user has one of the required roles for the current store.
     *
     * @param  array<StoreUserRole>  $requiredRoles
     */
    protected function checkGateRole($user, array $requiredRoles): bool
    {
        if (! app()->bound('current_store')) {
            return false;
        }

        $store = app('current_store');

        $storeUser = $user->stores()
            ->where('stores.id', $store->id)
            ->first();

        if (! $storeUser) {
            return false;
        }

        $pivotRole = $storeUser->pivot->role;
        $role = $pivotRole instanceof StoreUserRole
            ? $pivotRole
            : StoreUserRole::from($pivotRole);

        return in_array($role, $requiredRoles);
    }
}
