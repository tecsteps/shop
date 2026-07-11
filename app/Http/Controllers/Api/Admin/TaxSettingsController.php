<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTaxSettingsRequest;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TaxSettingsController extends Controller
{
    public function show(Store $store): JsonResponse
    {
        $this->ensureStore($store);

        return response()->json(['data' => TaxSettings::query()->find($store->id)]);
    }

    public function update(UpdateTaxSettingsRequest $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $settings = TaxSettings::query()->updateOrCreate(['store_id' => $store->id], $request->validated());

        return response()->json(['data' => $settings]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }
}
