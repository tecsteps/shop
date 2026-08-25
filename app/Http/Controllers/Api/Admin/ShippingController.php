<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index(int $storeId)
    {
        $zones = ShippingZone::with('rates')->get();

        return response()->json([
            'data' => $zones->map(fn (ShippingZone $zone) => [
                'id' => $zone->id,
                'store_id' => $zone->store_id,
                'name' => $zone->name,
                'countries_json' => $zone->countries_json,
                'regions_json' => $zone->regions_json,
                'rates' => $zone->rates->map(fn ($rate) => [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'type' => $rate->type,
                    'config_json' => $rate->config_json,
                    'is_active' => $rate->is_active,
                ]),
            ]),
        ]);
    }

    public function store(Request $request, int $storeId)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'countries_json' => ['required', 'array', 'min:1'],
            'regions_json' => ['sometimes', 'array'],
        ]);

        $zone = ShippingZone::create([
            'store_id' => app('current_store')->id,
            'name' => $validated['name'],
            'countries_json' => $validated['countries_json'],
            'regions_json' => $validated['regions_json'] ?? [],
        ]);

        return response()->json(['data' => ['id' => $zone->id, 'name' => $zone->name]], 201);
    }
}
