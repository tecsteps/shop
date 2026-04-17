<?php

namespace App\Auth;

use App\Support\Tenant\CurrentStore;
use Closure;
use Illuminate\Auth\DatabaseUserProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Builder;

class CustomerUserProvider extends DatabaseUserProvider
{
    public function __construct(
        \Illuminate\Database\ConnectionInterface $connection,
        \Illuminate\Contracts\Hashing\Hasher $hasher,
        string $table,
        private readonly Application $app,
    ) {
        parent::__construct($connection, $hasher, $table);
    }

    public function retrieveById($identifier)
    {
        $user = $this->newScopedQuery()
            ->where('id', $identifier)
            ->first();

        return $this->getGenericUser($user);
    }

    public function retrieveByToken($identifier, $token)
    {
        $user = $this->getGenericUser(
            $this->newScopedQuery()->where('id', $identifier)->first(),
        );

        return $user && $user->getRememberToken() && hash_equals($user->getRememberToken(), (string) $token)
            ? $user
            : null;
    }

    public function updateRememberToken(UserContract $user, $token): void
    {
        try {
            $this->newScopedQuery()
                ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
                ->update([$user->getRememberTokenName() => $token]);
        } catch (\Throwable) {
            // Customer remember tokens are optional in early schema iterations.
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            static fn (string $key): bool => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY,
        );

        if ($credentials === []) {
            return null;
        }

        $query = $this->newScopedQuery();

        foreach ($credentials as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } elseif ($value instanceof Closure) {
                $value($query);
            } else {
                $query->where($key, $value);
            }
        }

        return $this->getGenericUser($query->first());
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(UserContract $user, array $credentials, bool $force = false): void
    {
        if (! is_string($credentials['password'] ?? null)) {
            return;
        }

        $hashedPassword = $user->getAuthPassword();

        if (! $this->hasher->needsRehash($hashedPassword) && ! $force) {
            return;
        }

        $this->newScopedQuery()
            ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
            ->update(['password_hash' => $this->hasher->make($credentials['password'])]);
    }

    protected function getGenericUser($user)
    {
        if ($user === null) {
            return null;
        }

        $attributes = (array) $user;
        $attributes['password'] = $attributes['password_hash'] ?? null;
        $attributes['remember_token'] = $attributes['remember_token'] ?? null;

        return new GenericUser($attributes);
    }

    private function newScopedQuery(): Builder
    {
        $query = $this->connection->table($this->table);
        $storeId = $this->resolveStoreId();

        if ($storeId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('store_id', $storeId);
    }

    private function resolveStoreId(): ?int
    {
        if ($this->app->bound(CurrentStore::class)) {
            $store = $this->app->make(CurrentStore::class);

            if ($store instanceof CurrentStore) {
                return $store->id;
            }
        }

        if (! $this->app->bound('current_store')) {
            return null;
        }

        $store = $this->app->make('current_store');

        if ($store instanceof CurrentStore) {
            return $store->id;
        }

        if (is_array($store) && array_key_exists('id', $store)) {
            $id = $store['id'];

            if (is_int($id) || is_float($id) || is_string($id)) {
                return (int) $id;
            }
        }

        if (is_object($store) && isset($store->id) && (is_int($store->id) || is_float($store->id) || is_string($store->id))) {
            return (int) $store->id;
        }

        return null;
    }
}
