<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;

/**
 * Password broker manager that swaps in the store-scoped token repository
 * for the "customers" broker. The customer_password_reset_tokens table is
 * keyed by (store_id, email), which the default email-only repository
 * cannot write to (spec 06 §1.2).
 */
class CustomerPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * Create the token repository for a broker configuration.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createTokenRepository(array $config)
    {
        if (($config['table'] ?? null) !== 'customer_password_reset_tokens') {
            return parent::createTokenRepository($config);
        }

        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new CustomerTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            ($config['expire'] ?? 60) * 60,
            $config['throttle'] ?? 0,
        );
    }
}
