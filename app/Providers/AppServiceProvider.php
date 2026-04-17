<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $this->configureAuth();
        $this->configureRateLimiters();
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

    protected function configureAuth(): void
    {
        Auth::provider('customer', function (mixed $app, array $config): CustomerUserProvider {
            $model = class_exists(\App\Models\Customer::class)
                ? \App\Models\Customer::class
                : \App\Models\User::class;

            return new CustomerUserProvider(Hash::driver(), $model);
        });
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('api.admin', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->ip()));

        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) $request->session()->getId()));

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)
            ->by((string) $request->ip()));

        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) $request->ip()));

        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(100)
            ->by((string) $request->ip()));
    }
}
