<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use InvalidArgumentException;

class StoreScopedPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * Create a token repository instance based on the broker configuration.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createTokenRepository(array $config): TokenRepositoryInterface
    {
        if (($config['store_scoped'] ?? false) !== true) {
            return parent::createTokenRepository($config);
        }

        if (($config['driver'] ?? 'database') !== 'database') {
            throw new InvalidArgumentException('Store scoped password resets require the database token driver.');
        }

        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $decodedKey = base64_decode(substr($key, 7), true);

            if ($decodedKey !== false) {
                $key = $decodedKey;
            }
        }

        return new StoreScopedDatabaseTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            ($config['expire'] ?? 60) * 60,
            $config['throttle'] ?? 0,
        );
    }
}
