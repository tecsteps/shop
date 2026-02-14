<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        $this->registerAuthProviders();
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
     * Register custom authentication providers.
     */
    protected function registerAuthProviders(): void
    {
        Auth::provider('customer', function (Application $app, array $config): CustomerUserProvider {
            $connection = isset($config['connection']) ? (string) $config['connection'] : null;
            $table = (string) ($config['table'] ?? 'customers');

            return new CustomerUserProvider(
                connection: $app['db']->connection($connection),
                hasher: $app['hash'],
                table: $table,
                app: $app,
            );
        });
    }

    /**
     * Register application rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->ip()));

        RateLimiter::for('api.admin', function (Request $request): Limit {
            $userId = $request->user()?->getAuthIdentifier();

            return Limit::perMinute(60)->by($userId !== null
                ? sprintf('user:%s', $userId)
                : sprintf('ip:%s', (string) $request->ip()));
        });

        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)->by((string) $request->ip()));

        RateLimiter::for('checkout', function (Request $request): Limit {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;

            return Limit::perMinute(10)->by($sessionId !== null && $sessionId !== ''
                ? sprintf('session:%s', $sessionId)
                : sprintf('ip:%s', (string) $request->ip()));
        });

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by((string) $request->ip()));
        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)->by((string) $request->ip()));
    }
}
