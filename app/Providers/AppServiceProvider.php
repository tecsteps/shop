<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Enums\StoreUserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        //
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
