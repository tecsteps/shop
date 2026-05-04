<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Events\CheckoutCompleted;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\EnsureUserEmailIsVerified;
use App\Http\Middleware\ResolveStore;
use App\Listeners\DispatchWebhooks;
use App\Models\Collection as ProductCollection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Page;
use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Models\User;
use App\Observers\AuditModelObserver;
use App\Observers\ProductObserver;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\NavigationService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\SearchService;
use App\Services\ThemeSettingsService;
use App\Services\WebhookService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed as AuthFailed;
use Illuminate\Auth\Events\Login as AuthLogin;
use Illuminate\Auth\Events\Logout as AuthLogout;
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

        Auth::viaRequest('sanctum-compatible', function (Request $request): ?User {
            app()->forgetInstance('sanctum_personal_access_token');

            $plainTextToken = $request->bearerToken();

            if (! is_string($plainTextToken) || $plainTextToken === '') {
                return null;
            }

            $token = PersonalAccessToken::query()
                ->with('tokenable')
                ->where('token', hash('sha256', $this->plainTokenForHashing($plainTextToken)))
                ->first();

            if (! $token instanceof PersonalAccessToken || $token->isExpired() || ! $token->tokenable instanceof User) {
                return null;
            }

            $request->attributes->set('sanctum_personal_access_token', $token);
            app()->instance('sanctum_personal_access_token', $token);

            return $token->tokenable;
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

        RateLimiter::for('api.admin', function (Request $request): Limit {
            $token = $request->attributes->get('sanctum_personal_access_token');

            return Limit::perMinute(60)->by(match (true) {
                $token instanceof PersonalAccessToken => 'token:'.$token->getKey(),
                $request->user() instanceof User => 'user:'.$request->user()->getAuthIdentifier(),
                default => 'ip:'.$request->ip(),
            });
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
        foreach ([
            Product::class,
            ProductCollection::class,
            Discount::class,
            Page::class,
            Theme::class,
            Order::class,
            Fulfillment::class,
            Refund::class,
            NavigationMenu::class,
            ShippingZone::class,
            StoreSettings::class,
            TaxSettings::class,
        ] as $model) {
            $model::observe(AuditModelObserver::class);
        }

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
        Event::listen(AuthLogin::class, function (AuthLogin $event): void {
            $user = $event->user;

            if ($user instanceof User) {
                app(AuditLogger::class)->log('auth.login', userId: $user->getKey());
            }

            if ($user instanceof Customer) {
                app(AuditLogger::class)->log('customer.login', storeId: (int) $user->store_id, extra: [
                    'customer_id' => $user->getKey(),
                ]);
            }
        });

        Event::listen(AuthFailed::class, function (AuthFailed $event): void {
            $credentials = $event->credentials;
            $email = is_string($credentials['email'] ?? null) ? $credentials['email'] : null;

            app(AuditLogger::class)->log(
                event: $event->guard === 'customer' ? 'customer.failed_login' : 'auth.failed_login',
                extra: ['email' => $email],
            );
        });

        Event::listen(AuthLogout::class, function (AuthLogout $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.logout', userId: $event->user->getKey());
            }
        });

        foreach ([
            OrderCreated::class,
            OrderPaid::class,
            OrderCancelled::class,
            OrderRefunded::class,
            CheckoutCompleted::class,
            FulfillmentCreated::class,
            FulfillmentShipped::class,
            FulfillmentDelivered::class,
            ProductCreated::class,
            ProductUpdated::class,
            ProductDeleted::class,
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

    private function plainTokenForHashing(string $plainTextToken): string
    {
        if (str_contains($plainTextToken, '|')) {
            return (string) str($plainTextToken)->after('|');
        }

        return $plainTextToken;
    }
}
