<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTaxSettingsRequest;
use App\Http\Resources\Admin\TaxSettingsResource;
use App\Models\Store;
use App\Models\TaxSettings;

class TaxSettingsController extends Controller
{
    public function show(Store $store): TaxSettingsResource
    {
        return new TaxSettingsResource($this->taxSettings($store));
    }

    public function update(UpdateTaxSettingsRequest $request, Store $store): TaxSettingsResource
    {
        $settings = $this->taxSettings($store);
        $settings->update($request->validated());

        return new TaxSettingsResource($settings->refresh());
    }

    private function taxSettings(Store $store): TaxSettings
    {
        return TaxSettings::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => 'manual',
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [],
            ],
        );
    }
}
