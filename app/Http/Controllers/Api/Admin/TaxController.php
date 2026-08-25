<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxSettings;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function show(int $storeId)
    {
        $settings = TaxSettings::where('store_id', $storeId)->first();

        return response()->json(['data' => $settings?->toArray() ?? ['store_id' => $storeId, 'mode' => 'manual', 'prices_include_tax' => false, 'config_json' => []]]);
    }

    public function update(Request $request, int $storeId)
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:manual,provider'],
            'provider' => ['sometimes', 'in:stripe_tax,none'],
            'prices_include_tax' => ['required', 'boolean'],
            'config_json' => ['sometimes', 'array'],
        ]);

        $settings = TaxSettings::updateOrCreate(
            ['store_id' => $storeId],
            [
                'mode' => $validated['mode'],
                'provider' => $validated['provider'] ?? 'none',
                'prices_include_tax' => $validated['prices_include_tax'],
                'config_json' => $validated['config_json'] ?? [],
            ]
        );

        return response()->json(['data' => $settings->toArray()]);
    }
}
