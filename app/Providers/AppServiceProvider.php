<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Http\Middleware\ResolveStore;
use App\Services\NavigationService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
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
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuth();
        $this->configureRateLimiting();
        $this->configureLivewire();
    }

    /**
     * Keep the current store resolved on Livewire update requests so that
     * store-scoped queries inside interactive storefront components stay
     * tenant-isolated between page loads.
     */
    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveStore::class,
        ]);
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
    protected function configureAuth(): void
    {
        Auth::provider('customer-eloquent', function ($app, array $config): CustomerUserProvider {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Register the application's rate limiters (spec 02 section 7).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api.admin', function (Request $request): Limit {
            $token = $request->user()?->currentAccessToken();

            $key = $token instanceof PersonalAccessToken
                ? 'token:'.$token->getKey()
                : $request->ip();

            return Limit::perMinute(60)->by($key)->response($this->rateLimitResponse(...));
        });

        RateLimiter::for('api.storefront', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->ip())->response($this->rateLimitResponse(...));
        });

        RateLimiter::for('checkout', function (Request $request): Limit {
            $key = $request->hasSession() && $request->session()->isStarted()
                ? 'session:'.$request->session()->getId()
                : $request->ip();

            return Limit::perMinute(10)->by($key)->response($this->rateLimitResponse(...));
        });

        RateLimiter::for('search', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip())->response($this->rateLimitResponse(...));
        });

        RateLimiter::for('analytics', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip())->response($this->rateLimitResponse(...));
        });

        RateLimiter::for('webhooks', function (Request $request): Limit {
            return Limit::perMinute(100)->by($request->ip())->response($this->rateLimitResponse(...));
        });
    }

    /**
     * The 429 response body required by spec 02 section 7.
     *
     * @param  array<string, string>  $headers
     */
    protected function rateLimitResponse(Request $request, array $headers): JsonResponse
    {
        return response()->json([
            'message' => __('Too many requests. Please try again later.'),
            'retry_after' => (int) ($headers['Retry-After'] ?? 60),
        ], 429, $headers);
    }
}
