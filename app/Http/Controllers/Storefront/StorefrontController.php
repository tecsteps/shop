<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\Tenant\CurrentStore;
use Illuminate\Http\Request;

abstract class StorefrontController extends Controller
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

    protected function currentStoreModel(Request $request): Store
    {
        $store = Store::query()->find($this->currentStoreId($request));

        if (! $store instanceof Store) {
            abort(404, 'Store not found.');
        }

        return $store;
    }

    protected function formatMoney(int $amount, string $currency): string
    {
        return number_format($amount / 100, 2, '.', ',').' '.strtoupper($currency);
    }
}
