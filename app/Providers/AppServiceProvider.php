<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Auth\StoreScopedPasswordBrokerManager;
use App\Contracts\PaymentProvider as PaymentProviderContract;
use App\Contracts\TaxProvider;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutCompleted;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Events\FulfillmentCreated;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\DispatchWebhooks;
use App\Listeners\RecordAuthenticationEvent;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\MockPaymentProvider;
use App\Services\Tax\ManualTaxProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProviderContract::class, MockPaymentProvider::class);
        $this->app->bind(TaxProvider::class, ManualTaxProvider::class);
        Auth::provider('customer', fn ($app, array $config): CustomerUserProvider => new CustomerUserProvider($app['hash'], $config['model']));
        $this->app->extend('auth.password', fn ($manager, $app): StoreScopedPasswordBrokerManager => new StoreScopedPasswordBrokerManager($app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Model::preventLazyLoading(! app()->isProduction());
        Product::observe(ProductObserver::class);
        Gate::policy(\App\Models\Collection::class, \App\Policies\CollectionPolicy::class);
        Gate::policy(\App\Models\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(\App\Models\Discount::class, \App\Policies\DiscountPolicy::class);
        Gate::policy(\App\Models\Fulfillment::class, \App\Policies\FulfillmentPolicy::class);
        Gate::policy(\App\Models\Order::class, \App\Policies\OrderPolicy::class);
        Gate::policy(\App\Models\Page::class, \App\Policies\PagePolicy::class);
        Gate::policy(\App\Models\Product::class, \App\Policies\ProductPolicy::class);
        Gate::policy(\App\Models\Refund::class, \App\Policies\RefundPolicy::class);
        Gate::policy(\App\Models\Store::class, \App\Policies\StorePolicy::class);
        Gate::policy(\App\Models\Theme::class, \App\Policies\ThemePolicy::class);
        Event::listen([
            CheckoutAddressed::class,
            CheckoutCompleted::class,
            CheckoutExpired::class,
            CheckoutShippingSelected::class,
            FulfillmentCreated::class,
            OrderCreated::class,
            OrderFulfilled::class,
            OrderPaid::class,
            OrderRefunded::class,
            ProductCreated::class,
            ProductDeleted::class,
            ProductUpdated::class,
        ], DispatchWebhooks::class);
        Event::listen([
            \Illuminate\Auth\Events\Failed::class,
            \Illuminate\Auth\Events\Login::class,
            \Illuminate\Auth\Events\Logout::class,
        ], RecordAuthenticationEvent::class);
        Livewire::setUpdateRoute(function ($handle): mixed {
            return Route::post(EndpointResolver::updatePath(), $handle)
                ->middleware(['web', 'store.resolve'])
                ->name('shop.livewire.update');
        });
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('api.admin', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)->by($request->hasSession() ? $request->session()->getId() : $request->ip()));
        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(100)->by($request->ip()));
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
}
