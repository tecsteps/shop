<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Models\Store;
use App\Services\NavigationService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);

        Auth::provider('store_scoped_eloquent', function ($app, array $config): CustomerUserProvider {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });

        $this->app->singleton(ThemeSettingsService::class);
        $this->app->singleton(NavigationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureStorefrontViewData();
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
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;

            return Limit::perMinute(10)->by($sessionId ?: $request->ip());
        });

        Authenticate::redirectUsing(function (Request $request): string {
            if ($request->is('admin*')) {
                return route('admin.login');
            }

            if ($request->is('account*')) {
                return route('account.login');
            }

            return route('login');
        });
    }

    protected function configureStorefrontViewData(): void
    {
        View::composer('layouts.storefront', function (ViewInstance $view): void {
            $store = app()->bound('current_store') ? app('current_store') : null;

            if (! $store instanceof Store) {
                $view->with([
                    'themeSettings' => [],
                    'mainNavigation' => [],
                    'footerNavigation' => [],
                ]);

                return;
            }

            $themeSettings = app(ThemeSettingsService::class)->forStore($store);
            $navigation = app(NavigationService::class);

            $view->with([
                'themeSettings' => $themeSettings,
                'mainNavigation' => $navigation->forHandle($store, data_get($themeSettings, 'header.main_menu', 'main-menu')),
                'footerNavigation' => $navigation->forHandle($store, data_get($themeSettings, 'footer.menu', 'footer-menu')),
            ]);
        });
    }
}
