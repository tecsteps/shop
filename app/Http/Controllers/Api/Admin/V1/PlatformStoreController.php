<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\StorePlatformStoreRequest;
use App\Http\Resources\Admin\V1\StoreResource;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlatformStoreController extends Controller
{
    public function store(StorePlatformStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $store = DB::transaction(function () use ($request, $validated): Store {
            $store = Store::query()->create([
                'organization_id' => $validated['organization_id'],
                'name' => $validated['name'],
                'handle' => $validated['handle'],
                'status' => 'active',
                'default_currency' => strtoupper((string) $validated['default_currency']),
                'default_locale' => $validated['default_locale'],
                'timezone' => $validated['timezone'],
            ]);

            StoreSettings::query()->create([
                'store_id' => $store->getKey(),
                'settings_json' => [],
            ]);

            $user = $request->user();

            if ($user instanceof \App\Models\User) {
                $store->users()->syncWithoutDetaching([
                    $user->getKey() => ['role' => 'owner', 'created_at' => now()],
                ]);
            }

            return $store;
        });

        return StoreResource::make($store)
            ->response()
            ->setStatusCode(201);
    }
}
