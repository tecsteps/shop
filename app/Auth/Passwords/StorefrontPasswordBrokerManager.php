<?php

declare(strict_types=1);

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager as BasePasswordBrokerManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;

final class StorefrontPasswordBrokerManager extends BasePasswordBrokerManager
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function createTokenRepository(array $config)
    {
        if (($config['store_scoped'] ?? false) !== true) {
            return parent::createTokenRepository($config);
        }

        $key = $this->normalizeAppKey(
            $this->config()->get('app.key'),
        );

        $connectionName = $this->nullableStringConfig($config, 'connection');
        $table = $this->stringConfig($config, 'table');
        $expireMinutes = $this->intConfig($config, 'expire', 60);
        $throttleSeconds = $this->intConfig($config, 'throttle', 0);

        return new StoreScopedTokenRepository(
            connection: $this->database()->connection($connectionName),
            hasher: $this->hasher(),
            table: $table,
            hashKey: $key,
            expires: $expireMinutes * 60,
            throttle: $throttleSeconds,
        );
    }

    private function config(): ConfigRepository
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        return $config;
    }

    private function database(): DatabaseManager
    {
        /** @var DatabaseManager $database */
        $database = $this->app->make('db');

        return $database;
    }

    private function hasher(): Hasher
    {
        /** @var Hasher $hasher */
        $hasher = $this->app->make('hash');

        return $hasher;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function stringConfig(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Password broker config "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function nullableStringConfig(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf('Password broker config "%s" must be a string when provided.', $key));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function intConfig(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;

        if (! is_int($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Password broker config "%s" must be an integer.', $key));
        }

        return (int) $value;
    }

    private function normalizeAppKey(mixed $key): string
    {
        if (! is_string($key) || $key === '') {
            throw new InvalidArgumentException('Application key must be a non-empty string.');
        }

        if (! str_starts_with($key, 'base64:')) {
            return $key;
        }

        $decoded = base64_decode(substr($key, 7), true);

        if (! is_string($decoded) || $decoded === '') {
            throw new InvalidArgumentException('Application key base64 payload is invalid.');
        }

        return $decoded;
    }
}
