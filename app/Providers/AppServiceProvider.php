<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductStatusChanged;
use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\EnsureUserEmailIsVerified;
use App\Http\Middleware\ResolveStore;
use App\Listeners\DispatchWebhooks;
use App\Models\Product;
use App\Models\Store;
use App\Observers\ProductObserver;
use App\Services\AnalyticsService;
use App\Services\NavigationService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\SearchService;
use App\Services\ThemeSettingsService;
use App\Services\WebhookService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;
use Livewire\Livewire;

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
        $this->app->singleton(SearchService::class);
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(WebhookService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureEventListeners();
        $this->configureLivewireMiddleware();
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

        RateLimiter::for('search', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('analytics', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request): Limit {
            return Limit::perMinute(100)->by($request->ip());
        });

        Product::observe(ProductObserver::class);

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

    protected function configureEventListeners(): void
    {
        foreach ([
            OrderCreated::class,
            OrderPaid::class,
            OrderCancelled::class,
            OrderRefunded::class,
            FulfillmentCreated::class,
            FulfillmentShipped::class,
            FulfillmentDelivered::class,
            ProductStatusChanged::class,
        ] as $event) {
            Event::listen($event, DispatchWebhooks::class);
        }
    }

    protected function configureLivewireMiddleware(): void
    {
        Livewire::addPersistentMiddleware([
            EnsureUserEmailIsVerified::class,
            ResolveStore::class,
            CheckStoreRole::class,
        ]);
    }
}
