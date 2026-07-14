<?php

namespace App\Auth;

use Closure;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Foundation\Application;

final class CustomerPasswordBroker
{
    public function __construct(private readonly Application $app) {}

    /** @param array<string, mixed> $credentials */
    public function sendResetLink(array $credentials, ?Closure $callback = null): string
    {
        return $this->broker()->sendResetLink($credentials, $callback);
    }

    /** @param array<string, mixed> $credentials */
    public function reset(array $credentials, Closure $callback): string
    {
        return $this->broker()->reset($credentials, $callback);
    }

    private function broker(): PasswordBroker
    {
        $config = config('auth.passwords.customers');
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7));
        }
        $repository = new TenantDatabaseTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            ($config['expire'] ?? 60) * 60,
            $config['throttle'] ?? 0,
        );

        return new PasswordBroker(
            $repository,
            $this->app['auth']->createUserProvider($config['provider']),
            $this->app['events'],
            timeboxDuration: (int) config('auth.timebox_duration', 200000),
        );
    }
}
