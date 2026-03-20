<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Enums\StoreUserRole;
use App\Http\Middleware\ResolveStore;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use App\Services\NavigationService;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        $this->app->singleton(NavigationService::class);

        $this->app->singleton('current_store', function () {
            $hostname = request()->getHost();

            $storeId = Cache::remember(
                "store_domain:{$hostname}",
                300,
                fn () => StoreDomain::where('hostname', $hostname)->value('store_id')
            );

            if (! $storeId) {
                return null;
            }

            $store = Store::find($storeId);

            if ($store) {
                View::share('currentStore', $store);
            }

            return $store;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuth();
        $this->configureRateLimiting();
        $this->configureGates();
        $this->configureLivewire();
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
     * Configure custom auth providers.
     */
    protected function configureAuth(): void
    {
        Auth::provider('customer', function ($app, array $config) {
            return new CustomerUserProvider(
                $app['hash'],
                $config['model'],
            );
        });
    }

    /**
     * Configure rate limiting.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }

    /**
     * Configure authorization gates.
     */
    protected function configureGates(): void
    {
        $ownerOrAdminGates = [
            'manage-store-settings',
            'manage-staff',
            'manage-developers',
            'manage-shipping',
            'manage-taxes',
            'manage-search-settings',
            'manage-navigation',
        ];

        foreach ($ownerOrAdminGates as $gate) {
            Gate::define($gate, function (User $user) {
                if (! app()->bound('current_store')) {
                    return false;
                }

                $store = app('current_store');
                if (! $store instanceof Store) {
                    return false;
                }

                $role = $user->roleForStore($store);

                return $role !== null && in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin], true);
            });
        }

        Gate::define('view-analytics', function (User $user) {
            if (! app()->bound('current_store')) {
                return false;
            }

            $store = app('current_store');
            if (! $store instanceof Store) {
                return false;
            }

            $role = $user->roleForStore($store);

            return $role !== null && in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff], true);
        });
    }

    /**
     * Configure Livewire persistent middleware.
     */
    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveStore::class,
        ]);
    }
}
