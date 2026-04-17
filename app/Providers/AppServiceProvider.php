<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Listeners\DispatchOrderWebhooks;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Payments\MockPaymentProvider;
use App\Services\ThemeSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(ThemeSettingsService::class);
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->configureCustomerAuthProvider();
        $this->configureWebhookListeners();

        Product::observe(ProductObserver::class);
    }

    protected function configureWebhookListeners(): void
    {
        Event::listen(OrderCreated::class, [DispatchOrderWebhooks::class, 'handleCreated']);
        Event::listen(OrderPaid::class, [DispatchOrderWebhooks::class, 'handlePaid']);
        Event::listen(OrderFulfilled::class, [DispatchOrderWebhooks::class, 'handleFulfilled']);
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->ip()));
    }

    /**
     * Customer model is created in Phase 6. Guard/provider configured in advance.
     */
    protected function configureCustomerAuthProvider(): void
    {
        Auth::provider('customer', function ($app, array $config): CustomerUserProvider {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
            $model = $config['model'];

            return new CustomerUserProvider($app['hash'], $model);
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
}
