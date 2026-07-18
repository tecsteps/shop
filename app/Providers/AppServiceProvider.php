<?php

namespace App\Providers;

use App\Contracts\PaymentProvider;
use App\Http\Middleware\ResolveStore;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Payments\MockPaymentProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureLivewire();
        $this->configureAuthRedirects();

        Product::observe(ProductObserver::class);
    }

    /**
     * Ensure store resolution re-runs on Livewire's AJAX update requests, since
     * they hit a separate internal endpoint outside the "storefront"/"admin" route groups.
     */
    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveStore::class,
        ]);
    }

    /**
     * Send unauthenticated storefront customers to the customer login page instead
     * of the admin login route used by the default "auth" guard.
     */
    protected function configureAuthRedirects(): void
    {
        Authenticate::redirectUsing(function (Request $request): string {
            if (Str::startsWith((string) $request->route()?->getName(), 'storefront.')) {
                return route('storefront.account.login');
            }

            return route('admin.login');
        });

        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            if (Str::startsWith((string) $request->route()?->getName(), 'storefront.')) {
                return route('storefront.account.dashboard');
            }

            return route('admin.dashboard');
        });
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

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
