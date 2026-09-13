<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreShippingZoneRequest;
use App\Http\Requests\Api\Admin\UpdateShippingZoneRequest;
use App\Http\Resources\Api\Admin\ShippingZoneResource;
use App\Models\ShippingZone;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ShippingZoneController extends Controller
{
    public function __construct(protected CurrentStore $currentStore) {}

    public function index(int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', ShippingZone::class);

        $zones = ShippingZone::query()
            ->where('store_id', $this->currentStore->id())
            ->with('shippingRates')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => ShippingZoneResource::collection($zones),
        ]);
    }

    public function store(StoreShippingZoneRequest $request, int $storeId): JsonResponse
    {
        Gate::authorize('create', ShippingZone::class);

        $validated = $request->validated();

        $zone = ShippingZone::query()->create([
            'store_id' => $this->currentStore->id(),
            'name' => $validated['name'],
            'countries_json' => array_map('strtoupper', $validated['countries_json']),
            'regions_json' => array_map('strtoupper', $validated['regions_json'] ?? []),
        ]);

        return (new ShippingZoneResource($zone->load('shippingRates')))->response()->setStatusCode(201);
    }

    public function update(UpdateShippingZoneRequest $request, int $storeId, int $zoneId): JsonResponse
    {
        $zone = $this->findZone($zoneId);

        Gate::authorize('update', $zone);

        $validated = $request->validated();

        if (array_key_exists('name', $validated) && $validated['name'] !== null) {
            $zone->name = $validated['name'];
        }

        if (array_key_exists('countries_json', $validated) && $validated['countries_json'] !== null) {
            $zone->countries_json = array_map('strtoupper', $validated['countries_json']);
        }

        if (array_key_exists('regions_json', $validated) && $validated['regions_json'] !== null) {
            $zone->regions_json = array_map('strtoupper', $validated['regions_json']);
        }

        $zone->save();

        return (new ShippingZoneResource($zone->refresh()->load('shippingRates')))->response();
    }

    public function destroy(int $storeId, int $zoneId): JsonResponse
    {
        $zone = $this->findZone($zoneId);

        Gate::authorize('delete', $zone);

        $zone->delete();

        return response()->json(['message' => 'Shipping zone deleted']);
    }

    protected function findZone(int $zoneId): ShippingZone
    {
        return ShippingZone::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($zoneId)
            ->firstOrFail();
    }
}
