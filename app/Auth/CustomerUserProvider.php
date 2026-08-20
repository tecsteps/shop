<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;

class CustomerUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher, string $model)
    {
        parent::__construct($hasher, $model);
    }

    protected function newModelQuery($model = null): Builder
    {
        $model ??= $this->createModel();
        $query = $model->newQuery();

        if (app()->bound('current_store')) {
            $query->where($model->qualifyColumn('store_id'), app('current_store')->getKey());
        }

        return $query;
    }
}
