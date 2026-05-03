<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
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
        Auth::provider('store_scoped_eloquent', function ($app, array $config): CustomerUserProvider {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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

        RateLimiter::for('api.storefront', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('checkout', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->session()->getId() ?: $request->ip());
        });
    }
}
