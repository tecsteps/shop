<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShippingRateRequest;
use App\Http\Requests\StoreShippingZoneRequest;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ShippingZoneController extends Controller
{
    public function index(Store $store): JsonResponse
    {
        $this->ensureStore($store);

        return response()->json(['data' => ShippingZone::query()->with('rates')->get()]);
    }

    public function store(StoreShippingZoneRequest $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $zone = ShippingZone::query()->create(['store_id' => $store->id, ...$request->validated()]);

        return response()->json(['data' => $zone], Response::HTTP_CREATED);
    }

    public function update(StoreShippingZoneRequest $request, Store $store, ShippingZone $zone): JsonResponse
    {
        $this->ensureRelated($store, $zone);
        $zone->update($request->validated());

        return response()->json(['data' => $zone->refresh()->load('rates')]);
    }

    public function storeRate(StoreShippingRateRequest $request, Store $store, ShippingZone $zone): JsonResponse
    {
        $this->ensureRelated($store, $zone);
        $rate = $zone->rates()->create($request->validated());

        return response()->json(['data' => $rate], Response::HTTP_CREATED);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureRelated(Store $store, ShippingZone $zone): void
    {
        $this->ensureStore($store);
        abort_unless($zone->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}
