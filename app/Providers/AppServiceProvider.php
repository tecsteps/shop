<?php

namespace App\Providers;

use App\Auth\StoreScopedCustomerUserProvider;
use App\Auth\StoreScopedPasswordBrokerManager;
use App\Enums\StoreUserRole;
use App\Http\Middleware\ResolveStore;
use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        $this->app->extend('auth.password', function ($manager, $app): StoreScopedPasswordBrokerManager {
            return $manager instanceof StoreScopedPasswordBrokerManager
                ? $manager
                : new StoreScopedPasswordBrokerManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([ResolveStore::class]);
        Auth::provider('store-customers', fn ($app, array $config): StoreScopedCustomerUserProvider => new StoreScopedCustomerUserProvider($app['hash'], $config['model']));
        ResetPasswordNotification::createUrlUsing(function (mixed $notifiable, string $token): string {
            $route = $notifiable instanceof Customer ? 'storefront.password.reset' : 'admin.password.reset';

            return route($route, ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);
        });
        $this->configureDefaults();
        $this->configureRateLimits();
        $this->configureAuthorization();
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

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA cache_size = -20000');
        }
    }

    private function configureRateLimits(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('api.admin', function (Request $request): Limit {
            $tokenId = $request->attributes->get('api_token')?->getKey();

            if ($tokenId !== null) {
                return Limit::perMinute(60)->by('token:'.$tokenId);
            }

            $plainTextToken = $request->bearerToken();

            if ($plainTextToken !== null) {
                return Limit::perMinute(60)->by('token:'.hash('sha256', $plainTextToken));
            }

            return Limit::perMinute(60)->by('ip:'.($request->user()?->id ?? $request->ip()));
        });
        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)->by($request->hasSession() ? $request->session()->getId() : $request->ip()));
        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
    }

    private function configureAuthorization(): void
    {
        Gate::policy(\App\Models\Product::class, ProductPolicy::class);
        Gate::policy(\App\Models\Order::class, OrderPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);

        Gate::define('view-collections', function (User $user): bool {
            $store = app()->bound('current_store') ? app('current_store') : null;

            return $store instanceof Store && in_array($user->roleForStore($store), [
                StoreUserRole::Owner->value,
                StoreUserRole::Admin->value,
                StoreUserRole::Staff->value,
                StoreUserRole::Support->value,
            ], true);
        });

        Gate::define('view-discounts', function (User $user): bool {
            $store = app()->bound('current_store') ? app('current_store') : null;

            return $store instanceof Store && in_array($user->roleForStore($store), [
                StoreUserRole::Owner->value,
                StoreUserRole::Admin->value,
                StoreUserRole::Staff->value,
                StoreUserRole::Support->value,
            ], true);
        });

        foreach ([
            'manage-developers' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'manage-store-settings' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'manage-staff' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'delete-store' => [StoreUserRole::Owner->value],
            'manage-products' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value],
            'archive-products' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'manage-orders' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value],
            'view-orders' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value, StoreUserRole::Support->value],
            'process-refunds' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'create-fulfillments' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value],
            'manage-discounts' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value],
            'manage-themes' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'manage-apps' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'manage-navigation' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'view-analytics' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value],
            'manage-shipping' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value],
            'view-customers' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value, StoreUserRole::Support->value],
            'update-customers' => [StoreUserRole::Owner->value, StoreUserRole::Admin->value, StoreUserRole::Staff->value],
        ] as $ability => $roles) {
            Gate::define($ability, function (User $user) use ($roles): bool {
                $store = app()->bound('current_store') ? app('current_store') : null;

                return $store instanceof Store && in_array($user->roleForStore($store), $roles, true);
            });
        }

        Gate::define('view-admin', fn (User $user): bool => $user->stores()->exists());
        Gate::define('customer-account', fn (Customer $customer): bool => app()->bound('current_store') && (int) $customer->store_id === (int) app('current_store')->id);
    }
}
