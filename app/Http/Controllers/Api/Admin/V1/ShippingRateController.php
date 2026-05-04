<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\ShippingRateType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\ShippingRateResource;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingRateController extends Controller
{
    public function store(Request $request, Store $store, ShippingZone $shippingZone): JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessZoneBelongsToStore($shippingZone, $store);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_map(fn (ShippingRateType $type): string => $type->value, ShippingRateType::cases()))],
            'config_json' => ['required', 'array'],
            'config_json.price_amount' => ['nullable', 'integer', 'min:0'],
            'config_json.amount' => ['nullable', 'integer', 'min:0'],
            'config_json.currency' => ['nullable', 'string', 'size:3'],
            'config_json.tiers' => ['nullable', 'array'],
            'config_json.tiers.*.min_weight_g' => ['nullable', 'integer', 'min:0'],
            'config_json.tiers.*.max_weight_g' => ['nullable', 'integer', 'min:1'],
            'config_json.tiers.*.min_order_amount' => ['nullable', 'integer', 'min:0'],
            'config_json.tiers.*.max_order_amount' => ['nullable', 'integer', 'min:1'],
            'config_json.tiers.*.price_amount' => ['nullable', 'integer', 'min:0'],
            'config_json.ranges' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rate = ShippingRate::withoutGlobalScopes()->create([
            'zone_id' => $shippingZone->getKey(),
            'name' => $validated['name'],
            'type' => ShippingRateType::from($validated['type']),
            'config_json' => $this->normalizeConfig($validated['type'], $request->input('config_json', []), $store),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return ShippingRateResource::make($rate->load('zone.store'))
            ->response()
            ->setStatusCode(201);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessZoneBelongsToStore(ShippingZone $zone, Store $store): void
    {
        abort_unless((int) $zone->store_id === $store->getKey(), 404);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(string $type, array $config, Store $store): array
    {
        if (in_array($type, [ShippingRateType::Flat->value, ShippingRateType::Carrier->value], true)) {
            return [
                'amount' => (int) ($config['price_amount'] ?? $config['amount'] ?? 0),
                'currency' => strtoupper((string) ($config['currency'] ?? $store->default_currency)),
            ];
        }

        if ($type === ShippingRateType::Weight->value) {
            return [
                'currency' => strtoupper((string) ($config['currency'] ?? $store->default_currency)),
                'ranges' => collect($config['tiers'] ?? $config['ranges'] ?? [])
                    ->map(fn (array $tier): array => [
                        'min_g' => (int) ($tier['min_weight_g'] ?? $tier['min_g'] ?? 0),
                        'max_g' => array_key_exists('max_weight_g', $tier) ? $tier['max_weight_g'] : ($tier['max_g'] ?? null),
                        'amount' => (int) ($tier['price_amount'] ?? $tier['amount'] ?? 0),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return [
            'currency' => strtoupper((string) ($config['currency'] ?? $store->default_currency)),
            'ranges' => collect($config['tiers'] ?? $config['ranges'] ?? [])
                ->map(fn (array $tier): array => [
                    'min_amount' => (int) ($tier['min_order_amount'] ?? $tier['min_amount'] ?? 0),
                    'max_amount' => array_key_exists('max_order_amount', $tier) ? $tier['max_order_amount'] : ($tier['max_amount'] ?? null),
                    'amount' => (int) ($tier['price_amount'] ?? $tier['amount'] ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }
}
