<?php

namespace App\Tenancy;

use App\Models\Store;

/**
 * Holds the store resolved for the current request.
 *
 * The resolved Store model is bound in the container under the `current_store`
 * key (per spec 05/06). This service provides a typed accessor around it.
 */
class CurrentStore
{
    public function set(Store $store): void
    {
        app()->instance('current_store', $store);
    }

    public function get(): ?Store
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $store : null;
    }

    public function id(): ?int
    {
        return $this->get()?->getKey();
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }

    public function forget(): void
    {
        app()->forgetInstance('current_store');
    }
}
