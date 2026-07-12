<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Contracts\TaxProvider;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Payments\MockPaymentProvider;
use App\Services\Tax\ManualTaxProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        $this->app->singleton(PaymentProvider::class, MockPaymentProvider::class);
        $this->app->singleton(TaxProvider::class, ManualTaxProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthentication();
        $this->configureRateLimits();
        Product::observe(ProductObserver::class);

        if (config('database.default') === 'sqlite') {
            try {
                DB::statement('PRAGMA cache_size = -20000');
            } catch (\Throwable) {
                // The database file may not exist yet during the first Composer setup.
            }
        }
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

    private function configureAuthentication(): void
    {
        Auth::provider('tenant-customers', fn ($app, array $config): CustomerUserProvider => new CustomerUserProvider(
            $app['hash'],
            $config['model'],
        ));
    }

    private function configureRateLimits(): void
    {
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip().'|'.mb_strtolower((string) $request->input('email'))));
        RateLimiter::for('api.admin', fn ($request) => Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('api.storefront', fn ($request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('checkout', fn ($request) => Limit::perMinute(10)->by($request->session()->getId()));
        RateLimiter::for('search', fn ($request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('analytics', fn ($request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('webhooks', fn ($request) => Limit::perMinute(100)->by($request->ip()));
    }
}
