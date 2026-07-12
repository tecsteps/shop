<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingsController extends Controller
{
    public function zones(int $storeId): JsonResponse
    {
        return response()->json(['data' => ShippingZone::withoutGlobalScopes()->where('store_id', $storeId)->with('rates')->get()]);
    }

    public function storeZone(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string'], 'countries_json' => ['required', 'array'], 'regions_json' => ['sometimes', 'array']]);
        $zone = ShippingZone::withoutGlobalScopes()->create(['store_id' => $storeId, ...$data, 'regions_json' => $data['regions_json'] ?? []]);

        return response()->json(['data' => $zone], 201);
    }

    public function updateZone(Request $request, int $storeId, int $zoneId): JsonResponse
    {
        $zone = $this->zone($storeId, $zoneId);
        $zone->update($request->validate(['name' => ['sometimes', 'string'], 'countries_json' => ['sometimes', 'array'], 'regions_json' => ['sometimes', 'array']]));

        return response()->json(['data' => $zone->refresh()]);
    }

    public function storeRate(Request $request, int $storeId, int $zoneId): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string'], 'type' => ['required', 'in:flat,weight,price,carrier'], 'config_json' => ['required', 'array'], 'is_active' => ['sometimes', 'boolean']]);
        $rate = $this->zone($storeId, $zoneId)->rates()->create($data);

        return response()->json(['data' => $rate], 201);
    }

    public function tax(int $storeId): JsonResponse
    {
        return response()->json(['data' => TaxSettings::withoutGlobalScopes()->where('store_id', $storeId)->firstOrFail()]);
    }

    public function updateTax(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['mode' => ['required', 'in:manual,provider'], 'provider' => ['required', 'in:none,stripe_tax'], 'prices_include_tax' => ['required', 'boolean'], 'config_json' => ['required', 'array']]);
        $settings = TaxSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $storeId], $data);

        return response()->json(['data' => $settings]);
    }

    private function zone(int $storeId, int $id): ShippingZone
    {
        return ShippingZone::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($id);
    }
}
