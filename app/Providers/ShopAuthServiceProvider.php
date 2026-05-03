<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Auth\StoreScopedPasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class ShopAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerStoreScopedPasswordBroker();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerStoreScopedPasswordBroker();

        Auth::provider('store_scoped_eloquent', function ($app, array $config): CustomerUserProvider {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });
    }

    private function registerStoreScopedPasswordBroker(): void
    {
        $this->app->removeDeferredServices(['auth.password', 'auth.password.broker']);
        $this->app->forgetInstance('auth.password');
        $this->app->forgetInstance('auth.password.broker');

        $this->app->singleton('auth.password', function ($app): StoreScopedPasswordBrokerManager {
            return new StoreScopedPasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app): PasswordBroker {
            return $app->make('auth.password')->broker();
        });
    }
}
