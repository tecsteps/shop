<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;

class StoreScopedPasswordBrokerManager extends PasswordBrokerManager implements PasswordBrokerFactory
{
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (($config['table'] ?? null) === 'customer_password_reset_tokens') {
            return new StoreScopedTokenRepository(
                $this->app['db']->connection($config['connection'] ?? null),
                $this->app['hash'],
                $config['table'],
                $key,
                ($config['expire'] ?? 60) * 60,
                $config['throttle'] ?? 0,
            );
        }

        return new DatabaseTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            ($config['expire'] ?? 60) * 60,
            $config['throttle'] ?? 0,
        );
    }
}
