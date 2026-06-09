<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a customer by their unique identifier, scoped to the current store.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        $model = $this->createModel();

        $query = $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier);

        if ($storeId = $this->currentStoreId()) {
            $query->where('store_id', $storeId);
        }

        return $query->first();
    }

    /**
     * Remember-me tokens are not supported for customers.
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    /**
     * Retrieve a customer by the given credentials, always scoped to the current store.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (empty($credentials)) {
            return null;
        }

        if ($storeId = $this->currentStoreId()) {
            $credentials['store_id'] = $storeId;
        }

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Resolve the id of the current store bound in the container, if any.
     */
    protected function currentStoreId(): ?int
    {
        return app()->bound('current_store') ? app('current_store')->getKey() : null;
    }
}
