<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\ShippingRateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreShippingRateRequest;
use App\Http\Resources\Api\Admin\ShippingRateResource;
use App\Models\ShippingZone;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ShippingRateController extends Controller
{
    public function __construct(protected CurrentStore $currentStore) {}

    public function store(StoreShippingRateRequest $request, int $storeId, int $zoneId): JsonResponse
    {
        $zone = $this->findZone($zoneId);

        Gate::authorize('update', $zone);

        $validated = $request->validated();

        $rate = $zone->shippingRates()->create([
            'name' => $validated['name'],
            'type' => ShippingRateType::from($validated['type']),
            'config_json' => $validated['config_json'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return (new ShippingRateResource($rate))->response()->setStatusCode(201);
    }

    public function destroy(int $storeId, int $zoneId, int $rateId): JsonResponse
    {
        $zone = $this->findZone($zoneId);

        Gate::authorize('update', $zone);

        $rate = $zone->shippingRates()->whereKey($rateId)->firstOrFail();
        $rate->delete();

        return response()->json(['message' => 'Shipping rate deleted']);
    }

    protected function findZone(int $zoneId): ShippingZone
    {
        return ShippingZone::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($zoneId)
            ->firstOrFail();
    }
}
