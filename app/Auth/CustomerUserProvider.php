<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerUserProvider implements UserProvider
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        protected Hasher $hasher,
        protected string $model,
    ) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        $query = $this->newModelQuery();
        $this->constrainToCurrentStore($query);

        /** @var Authenticatable|null $model */
        $model = $query->find($identifier);

        return $model;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $model = $this->createModel();

        $query = $this->newModelQuery()
            ->where($model->getAuthIdentifierName(), $identifier);

        $this->constrainToCurrentStore($query);

        /** @var Authenticatable|null $retrieved */
        $retrieved = $query->first();

        if ($retrieved === null) {
            return null;
        }

        $rememberToken = $retrieved->getRememberToken();

        if ($rememberToken !== null && hash_equals($rememberToken, $token)) {
            return $retrieved;
        }

        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);

        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->save();
        $user->timestamps = $timestamps;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials)
            || (count($credentials) === 1 && Str::contains(array_key_first($credentials), 'password'))
        ) {
            return null;
        }

        $query = $this->newModelQuery();
        $this->constrainToCurrentStore($query);

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof \Illuminate\Contracts\Support\Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        /** @var Authenticatable|null $result */
        $result = $query->first();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? null;

        if (! is_string($plain) || $plain === '') {
            return false;
        }

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $plain = $credentials['password'] ?? null;

        if (! is_string($plain) || $plain === '') {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make($plain),
        ])->save();
    }

    protected function createModel(): Model
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    protected function newModelQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->createModel()->newQuery();
    }

    protected function constrainToCurrentStore(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        /** @var Store $store */
        $store = app('current_store');

        $query->where('store_id', $store->id);
    }
}
