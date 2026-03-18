<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Http\Middleware\ResolveStore;
use App\Services\Payments\MockPaymentProvider;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeSettingsService::class);
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuth();
        $this->configureRateLimiting();
        $this->configureLivewire();
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
        Auth::provider('customer', function ($app, array $config) {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }

    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveStore::class,
        ]);
    }
}
