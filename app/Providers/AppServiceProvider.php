<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\StoreUser;
use App\Models\User;
use App\Services\AuditLogger;
use App\Tenancy\CurrentStore;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentStore::class);
        $this->app->bind(
            \App\Contracts\PaymentProvider::class,
            \App\Services\Payment\MockPaymentProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureCustomerProvider();
        $this->configureGates();
        $this->configureRateLimiting();
        $this->configureAuditListeners();
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

    protected function configureCustomerProvider(): void
    {
        Auth::provider('customer-eloquent', fn ($app, array $config): CustomerUserProvider => new CustomerUserProvider(
            $app['hash'],
            $config['model'],
        ));
    }

    protected function configureGates(): void
    {
        $hasStoreRole = function (User $user, array $roles): bool {
            $storeId = app(CurrentStore::class)->id();

            if ($storeId === null) {
                return false;
            }

            $role = StoreUser::query()
                ->where('store_id', $storeId)
                ->where('user_id', $user->getKey())
                ->value('role');

            $role = $role instanceof StoreUserRole ? $role : StoreUserRole::tryFrom((string) $role);

            return in_array($role, $roles, true);
        };

        $ownerAdmin = [StoreUserRole::Owner, StoreUserRole::Admin];
        $ownerAdminStaff = [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff];

        Gate::define('manage-store-settings', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('manage-staff', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('manage-developers', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('view-analytics', fn (User $user): bool => $hasStoreRole($user, $ownerAdminStaff));
        Gate::define('manage-shipping', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('manage-taxes', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('manage-search-settings', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('manage-navigation', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));
        Gate::define('manage-apps', fn (User $user): bool => $hasStoreRole($user, $ownerAdmin));

        Gate::define('manage-platform', fn (User $user): bool => StoreUser::query()
            ->where('user_id', $user->getKey())
            ->where('role', StoreUserRole::Owner->value)
            ->exists());
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api.admin', fn (Request $request): Limit => Limit::perMinute(60)->by(
            $request->user()?->getKey() ?? $request->ip()
        ));

        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)->by(
            $request->session()->getId() ?: $request->ip()
        ));

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(100)->by($request->ip()));
    }

    protected function configureAuditListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $audit = app(AuditLogger::class);

            if ($event->user instanceof Customer) {
                $audit->log('customer.login', storeId: $event->user->store_id, extra: [
                    'customer_id' => $event->user->getKey(),
                ]);

                return;
            }

            $audit->log('auth.login', userId: $event->user->getKey());
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $audit = app(AuditLogger::class);
            $email = is_array($event->credentials) ? ($event->credentials['email'] ?? null) : null;

            if ($event->guard === 'customer') {
                $audit->log('customer.failed_login', storeId: app(CurrentStore::class)->id(), extra: [
                    'email' => $email,
                ]);

                return;
            }

            $audit->log('auth.failed_login', extra: ['email' => $email]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.logout', userId: $event->user->getKey());
            }
        });
    }
}
