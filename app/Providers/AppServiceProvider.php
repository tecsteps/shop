<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Contracts\PaymentProvider;
use App\Enums\StoreUserRole;
use App\Http\Middleware\ResolveStore;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Payments\MockPaymentProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
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
        $this->configureAuthentication();
        $this->configureLivewire();
        $this->configureRateLimiters();
        $this->configureGates();
        Product::observe(ProductObserver::class);
    }

    private function configureAuthentication(): void
    {
        Auth::provider('store-customers', fn ($app, array $config): CustomerUserProvider => new CustomerUserProvider($app['hash'], $config['model']));
    }

    private function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveStore::class,
        ]);
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('api.admin', fn (Request $request): Limit => Limit::perMinute(60)->by((string) ($request->user()?->currentAccessToken()?->id ?? $request->user()?->id ?? $request->ip())));
        RateLimiter::for('api.storefront', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)->by($request->hasSession() ? $request->session()->getId() : $request->ip()));
        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('analytics', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(100)->by($request->ip()));
    }

    private function configureGates(): void
    {
        $gates = [
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

        foreach ($gates as $name => $roles) {
            Gate::define($name, fn ($user): bool => app()->bound('current_store') && in_array($user->roleForStore(app('current_store')), $roles, true));
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
}
