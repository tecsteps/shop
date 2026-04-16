<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Http\Middleware\ResolveStore;
use App\Services\Payments\MockPaymentProvider;
use App\Services\Payments\PaymentProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerCustomerAuthProvider();
        $this->registerRateLimiters();
        $this->configureLivewireUpdateRoute();
    }

    protected function configureLivewireUpdateRoute(): void
    {
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', ResolveStore::class.':storefront']);
        });
    }

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

    protected function registerCustomerAuthProvider(): void
    {
        Auth::provider('customer', function ($app, array $config) {
            return new CustomerUserProvider(
                $app->make(Hasher::class),
                $config['model'],
            );
        });
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('customer-login', function (Request $request): Limit {
            $throttleKey = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('checkout', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
