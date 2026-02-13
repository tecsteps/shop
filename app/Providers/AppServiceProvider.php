<?php

namespace App\Providers;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->configureGates();
    }

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

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api.admin', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api.storefront', function ($request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('checkout', function ($request) {
            return Limit::perMinute(10)->by($request->session()->getId());
        });

        RateLimiter::for('search', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('analytics', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    protected function configureGates(): void
    {
        $managerGate = function (User $user): bool {
            if (! app()->bound('current_store')) {
                return false;
            }

            /** @var Store $store */
            $store = app('current_store');
            $role = $user->roleForStore($store);

            return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin], true);
        };

        Gate::define('manage-store-settings', $managerGate);
        Gate::define('manage-staff', $managerGate);
        Gate::define('manage-developers', function (User $user): bool {
            if (! app()->bound('current_store')) {
                return false;
            }

            /** @var Store $store */
            $store = app('current_store');

            return $user->roleForStore($store) === StoreUserRole::Owner;
        });
        Gate::define('view-analytics', $managerGate);
        Gate::define('manage-shipping', $managerGate);
        Gate::define('manage-taxes', $managerGate);
        Gate::define('manage-search-settings', $managerGate);
        Gate::define('manage-navigation', $managerGate);
        Gate::define('manage-apps', function (User $user): bool {
            if (! app()->bound('current_store')) {
                return false;
            }

            /** @var Store $store */
            $store = app('current_store');

            return $user->roleForStore($store) === StoreUserRole::Owner;
        });
    }
}
