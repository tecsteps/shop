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
        $this->configureCustomerProvider();
        $this->configureRateLimiting();
        $this->configureGates();
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
    protected function configureCustomerProvider(): void
    {
        Auth::provider('eloquent.customer', function (Application $app, array $config) {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Register the application's named rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('api.admin', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api.storefront', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->session()->getId()));

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('analytics', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(100)->by($request->ip()));
    }

    /**
     * Register gates for non-model operations.
     *
     * Each gate resolves the current store from the container and checks the
     * acting user's role via the store_users relationship.
     */
    protected function configureGates(): void
    {
        $gates = [
            'manage-store-settings' => StoreUserRole::ownerOrAdmin(),
            'manage-staff' => StoreUserRole::ownerOrAdmin(),
            'manage-developers' => StoreUserRole::ownerOrAdmin(),
            'view-analytics' => StoreUserRole::ownerAdminOrStaff(),
            'manage-shipping' => StoreUserRole::ownerOrAdmin(),
            'manage-taxes' => StoreUserRole::ownerOrAdmin(),
            'manage-search-settings' => StoreUserRole::ownerOrAdmin(),
            'manage-navigation' => StoreUserRole::ownerOrAdmin(),
            'manage-apps' => StoreUserRole::ownerOrAdmin(),
        ];

        foreach ($gates as $name => $roles) {
            Gate::define($name, function (User $user) use ($roles): bool {
                if (! app()->bound('current_store')) {
                    return false;
                }

                $store = app('current_store');
                $role = $user->roleForStore($store);

                return $role !== null && in_array($role, $roles, true);
            });
        }
    }
}
