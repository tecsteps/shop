<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingRateRequest;
use App\Http\Requests\Admin\StoreShippingZoneRequest;
use App\Http\Requests\Admin\UpdateShippingZoneRequest;
use App\Http\Resources\Admin\ShippingZoneResource;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class ShippingController extends Controller
{
    public function index(Store $store): AnonymousResourceCollection
    {
        return ShippingZoneResource::collection(
            ShippingZone::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->with('rates')
                ->orderBy('name')
                ->get()
        );
    }

    public function storeZone(StoreShippingZoneRequest $request, Store $store): JsonResponse
    {
        $zone = ShippingZone::withoutGlobalScopes()->create([
            ...$request->validated(),
            'store_id' => $store->id,
            'regions_json' => $request->validated('regions_json') ?? [],
        ]);

        return (new ShippingZoneResource($zone->load('rates')))
            ->response()
            ->setStatusCode(201);
    }

    public function updateZone(UpdateShippingZoneRequest $request, Store $store, int $zone): ShippingZoneResource
    {
        $shippingZone = $this->findZone($store, $zone);
        $shippingZone->update($request->validated());

        return new ShippingZoneResource($shippingZone->refresh()->load('rates'));
    }

    public function storeRate(StoreShippingRateRequest $request, Store $store, int $zone): JsonResponse
    {
        $shippingZone = $this->findZone($store, $zone);
        $validated = $request->validated();

        $shippingZone->rates()->create([
            ...Arr::only($validated, ['name', 'type', 'config_json']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return (new ShippingZoneResource($shippingZone->refresh()->load('rates')))
            ->response()
            ->setStatusCode(201);
    }

    private function findZone(Store $store, int $zoneId): ShippingZone
    {
        return ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($zoneId)
            ->firstOrFail();
    }
}
