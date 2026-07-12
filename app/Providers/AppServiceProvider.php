<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Contracts\TaxProvider;
use App\Enums\StoreUserRole;
use App\Listeners\ShopEventSubscriber;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Refund;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Models\User;
use App\Observers\AuditableObserver;
use App\Observers\ProductMediaObserver;
use App\Observers\ProductObserver;
use App\Services\AuditLogger;
use App\Services\Payments\MockPaymentProvider;
use App\Services\Tax\ManualTaxProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->configureGates();
        Product::observe(ProductObserver::class);
        ProductMedia::observe(ProductMediaObserver::class);
        foreach ([
            Product::class,
            Collection::class,
            Discount::class,
            Page::class,
            Theme::class,
            Order::class,
            Fulfillment::class,
            Refund::class,
            NavigationMenu::class,
            ShippingZone::class,
            TaxSettings::class,
        ] as $model) {
            $model::observe(AuditableObserver::class);
        }
        Event::subscribe(ShopEventSubscriber::class);
        $this->configureAuditEvents();
        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $path = $notifiable instanceof \App\Models\Customer ? '/reset-password/' : '/admin/reset-password/';

            return url($path.$token).'?email='.urlencode($notifiable->getEmailForPasswordReset());
        });

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

    private function configureAuditEvents(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $customer = $event->user instanceof Customer;
            app(AuditLogger::class)->log(
                $customer ? 'customer.login' : 'auth.login',
                $customer ? null : (int) $event->user->getAuthIdentifier(),
                $this->currentStoreId(),
                extra: $customer ? ['customer_id' => (int) $event->user->getAuthIdentifier()] : [],
            );
        });
        Event::listen(Failed::class, function (Failed $event): void {
            $customer = $event->guard === 'customer';
            app(AuditLogger::class)->log(
                $customer ? 'customer.failed_login' : 'auth.failed_login',
                storeId: $this->currentStoreId(),
                extra: ['email' => (string) ($event->credentials['email'] ?? '')],
            );
        });
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user !== null) {
                app(AuditLogger::class)->log(
                    $event->user instanceof Customer ? 'customer.logout' : 'auth.logout',
                    $event->user instanceof Customer ? null : (int) $event->user->getAuthIdentifier(),
                    $this->currentStoreId(),
                );
            }
        });
    }

    private function configureGates(): void
    {
        $definitions = [
            'manage-store-settings' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'manage-staff' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'manage-developers' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'view-analytics' => [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff],
            'manage-shipping' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'manage-taxes' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'manage-search-settings' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'manage-navigation' => [StoreUserRole::Owner, StoreUserRole::Admin],
            'manage-apps' => [StoreUserRole::Owner, StoreUserRole::Admin],
        ];

        foreach ($definitions as $ability => $roles) {
            Gate::define($ability, function (User $user) use ($roles): bool {
                if (! app()->bound('current_store')) {
                    return false;
                }
                $store = app('current_store');
                $store = $store instanceof Store ? $store : Store::query()->find($store);
                $role = $store === null ? null : $user->roleForStore($store);

                return $role !== null && in_array($role, $roles, true);
            });
        }
    }

    private function currentStoreId(): ?int
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return (int) ($store instanceof \App\Models\Store ? $store->getKey() : $store);
    }
}
