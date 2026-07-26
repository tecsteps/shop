<?php

namespace App\Providers;

use App\Auth\CustomerPasswordBrokerManager;
use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Enums\StoreUserRole;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Listeners\WriteAuditLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Observers\ProductObserver;
use App\Services\Payments\MockPaymentProvider;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeSettingsService::class);

        // Mock PSP: in-process payment provider (spec 05 §10).
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuth();
        $this->configureGates();
        $this->configureRateLimiting();
        $this->configureAuditLog();
        $this->configureWebhookDispatch();

        // Keep the products_fts full-text index in sync (spec 05 §16.2).
        Product::observe(ProductObserver::class);

        // Anonymous storefront components: <x-storefront::product-card ... />
        Blade::anonymousComponentPath(resource_path('views/storefront/components'), 'storefront');

        // Anonymous admin views (layouts): <x-admin::layouts.auth ... />
        Blade::anonymousComponentPath(resource_path('views/admin'), 'admin');
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
     * Register the store-scoped customer user provider.
     */
    protected function configureAuth(): void
    {
        Auth::provider('customer', function (Application $app, array $config): CustomerUserProvider {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });

        // Store-scoped password broker for storefront customers (spec 06 §1.2).
        // The framework's PasswordResetServiceProvider is deferred and would
        // lazily rebind these services on first resolution, so its deferred
        // entries are removed in favour of this binding. This must run in
        // boot(): the deferred service map is only populated after all
        // register() calls.
        $this->app->removeDeferredServices(['auth.password', 'auth.password.broker']);
        $this->app->singleton('auth.password', fn ($app): CustomerPasswordBrokerManager => new CustomerPasswordBrokerManager($app));
        $this->app->bind('auth.password.broker', fn ($app) => $app['auth.password']->broker());

        // Password reset links point at the admin form for users and at the
        // storefront form for customers (spec 06 §1.1/§1.2).
        ResetPasswordNotification::createUrlUsing(function (Authenticatable $notifiable, string $token): string {
            $route = $notifiable instanceof Customer
                ? 'storefront.password.reset'
                : 'admin.password.reset';

            return route($route, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }

    /**
     * Register gates for non-model operations (spec 06 §2.5).
     */
    protected function configureGates(): void
    {
        $ownerOrAdmin = [StoreUserRole::Owner, StoreUserRole::Admin];
        $ownerAdminOrStaff = [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff];

        Gate::define('manage-store-settings', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('manage-staff', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('manage-developers', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('view-analytics', fn (User $user): bool => $this->gateAllows($user, $ownerAdminOrStaff));
        Gate::define('manage-shipping', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('manage-taxes', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('manage-search-settings', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('manage-navigation', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
        Gate::define('manage-apps', fn (User $user): bool => $this->gateAllows($user, $ownerOrAdmin));
    }

    /**
     * Resolve the current store from the container and check the user's
     * role membership against the given roles.
     *
     * @param  array<int, StoreUserRole>  $roles
     */
    protected function gateAllows(User $user, array $roles): bool
    {
        if (! app()->bound('current_store')) {
            return false;
        }

        $role = $user->roleForStore(app('current_store'));

        return $role !== null && in_array($role, $roles, true);
    }

    /**
     * Register the audit log listener for order lifecycle events
     * (spec 05 §17, spec 06 §4.6).
     */
    protected function configureAuditLog(): void
    {
        Event::listen([
            OrderCreated::class,
            OrderPaid::class,
            OrderCancelled::class,
            OrderRefunded::class,
            FulfillmentShipped::class,
            \App\Events\ProductCreated::class,
            \App\Events\ProductUpdated::class,
        ], WriteAuditLog::class);

        // Admin panel logins (spec 06 §4.6); the customer guard is ignored.
        Event::listen(\Illuminate\Auth\Events\Login::class, \App\Listeners\WriteAuthAuditLog::class);

        // Customer-facing order lifecycle emails (spec 05 §17). The listener
        // is failure-safe: mail errors are reported, never propagated.
        Event::listen([
            OrderCreated::class,
            OrderCancelled::class,
            OrderRefunded::class,
            FulfillmentShipped::class,
        ], \App\Listeners\SendOrderEmails::class);
    }

    /**
     * Register the webhook dispatcher for all domain events that have
     * webhook counterparts (spec 05 §13.1).
     */
    protected function configureWebhookDispatch(): void
    {
        Event::listen([
            \App\Events\OrderCreated::class,
            \App\Events\OrderPaid::class,
            \App\Events\OrderFulfilled::class,
            \App\Events\OrderRefunded::class,
            \App\Events\ProductCreated::class,
            \App\Events\ProductUpdated::class,
            \App\Events\ProductDeleted::class,
            \App\Events\CheckoutCompleted::class,
        ], \App\Listeners\DispatchWebhooks::class);
    }

    /**
     * Register the application's rate limiters (spec 06 §4.2).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('api.admin', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)->by(
            $request->hasSession() ? $request->session()->getId() : $request->ip(),
        ));

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(100)->by($request->ip()));
    }
}
