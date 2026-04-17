<?php

namespace App\Providers;

use App\Auth\CustomerTokenRepository;
use App\Auth\CustomerUserProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Contracts\PaymentProvider::class, \App\Services\Payments\MockPaymentProvider::class);

        $this->app->extend('auth.password', function (mixed $manager, mixed $app): mixed {
            return new class($app) extends \Illuminate\Auth\Passwords\PasswordBrokerManager
            {
                protected function resolve($name)
                {
                    if ($name === 'customers') {
                        $config = $this->app['config']['auth.passwords.customers'];
                        $key = (string) $this->app['config']['app.key'];

                        if (str_starts_with($key, 'base64:')) {
                            $key = base64_decode(substr($key, 7));
                        }

                        $repository = new CustomerTokenRepository(
                            $this->app['db']->connection(),
                            $this->app['hash'],
                            (string) $config['table'],
                            $key,
                            ((int) ($config['expire'] ?? 60)) * 60,
                            (int) ($config['throttle'] ?? 0),
                        );

                        return new PasswordBroker(
                            $repository,
                            $this->app['auth']->createUserProvider('customers'),
                            $this->app['events'] ?? null,
                        );
                    }

                    return parent::resolve($name);
                }
            };
        });
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

        PasswordRule::defaults(fn (): ?PasswordRule => app()->isProduction()
            ? PasswordRule::min(12)
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
            return new CustomerUserProvider(Hash::driver(), \App\Models\Customer::class);
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
