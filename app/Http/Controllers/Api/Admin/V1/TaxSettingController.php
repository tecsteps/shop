<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\UpdateTaxSettingsRequest;
use App\Http\Resources\Api\Admin\TaxSettingResource;
use App\Models\TaxSetting;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TaxSettingController extends Controller
{
    public function __construct(protected CurrentStore $currentStore) {}

    public function show(int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', TaxSetting::class);

        $settings = $this->findOrCreateSettings();

        return (new TaxSettingResource($settings))->response()->setStatusCode(200);
    }

    public function update(UpdateTaxSettingsRequest $request, int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', TaxSetting::class);

        $settings = $this->findOrCreateSettings();

        Gate::authorize('update', $settings);

        $validated = $request->validated();

        $settings->mode = TaxMode::from($validated['mode']);
        $settings->provider = TaxProvider::from($validated['provider'] ?? TaxProvider::None->value);
        $settings->prices_include_tax = (bool) $validated['prices_include_tax'];
        $settings->config_json = $validated['config_json'];
        $settings->save();

        return (new TaxSettingResource($settings->refresh()))->response()->setStatusCode(200);
    }

    protected function findOrCreateSettings(): TaxSetting
    {
        return TaxSetting::query()->firstOrCreate(
            ['store_id' => $this->currentStore->id()],
            [
                'mode' => TaxMode::Manual,
                'provider' => TaxProvider::None,
                'prices_include_tax' => false,
                'config_json' => [],
            ]
        );
    }
}
