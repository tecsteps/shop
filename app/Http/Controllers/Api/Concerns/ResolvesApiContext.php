<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Store;
use App\Support\Tenant\CurrentStore;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

trait ResolvesApiContext
{
    protected function currentStore(Request $request): CurrentStore
    {
        $attributeStore = $request->attributes->get('current_store');

        if ($attributeStore instanceof CurrentStore) {
            return $attributeStore;
        }

        if (app()->bound(CurrentStore::class)) {
            $containerStore = app(CurrentStore::class);

            if ($containerStore instanceof CurrentStore) {
                return $containerStore;
            }
        }

        abort(404, 'Store not found.');
    }

    protected function currentStoreId(Request $request): int
    {
        return $this->currentStore($request)->id;
    }

    protected function scopedStoreId(Request $request, int|string|null $routeStoreId = null): int
    {
        $currentStoreId = $this->currentStoreId($request);

        if ($routeStoreId === null) {
            return $currentStoreId;
        }

        if ((int) $routeStoreId !== $currentStoreId) {
            abort(404, 'Store not found.');
        }

        return $currentStoreId;
    }

    protected function currentStoreModel(Request $request): Store
    {
        $store = Store::query()->find($this->currentStoreId($request));

        if (! $store instanceof Store) {
            abort(404, 'Store not found.');
        }

        return $store;
    }

    protected function resolveService(string $serviceClass): ?object
    {
        if (! class_exists($serviceClass)) {
            return null;
        }

        try {
            $service = app($serviceClass);

            return is_object($service) ? $service : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    protected function iso(?CarbonInterface $date): ?string
    {
        return $date?->toISOString();
    }
}
