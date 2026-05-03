<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * @param  mixed  $identifier
     * @return (\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model)|null
     */
    public function retrieveById($identifier)
    {
        $store = $this->currentStore();

        if (! $store) {
            return null;
        }

        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where('store_id', $store->getKey())
            ->first();
    }

    /**
     * @param  mixed  $identifier
     * @param  string  $token
     * @return (\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model)|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $store = $this->currentStore();

        if (! $store) {
            return null;
        }

        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where('store_id', $store->getKey())
            ->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $retrievedModel : null;
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return (\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model)|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $store = $this->currentStore();

        if (! $store) {
            return null;
        }

        $credentials['store_id'] = $store->getKey();

        return parent::retrieveByCredentials($credentials);
    }

    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token): void
    {
        if (! $this->currentStore()) {
            return;
        }

        parent::updateRememberToken($user, $token);
    }

    private function currentStore(): ?Store
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $store : null;
    }
}
