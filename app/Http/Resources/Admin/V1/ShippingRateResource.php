<?php

namespace App\Http\Resources\Admin\V1;

use App\Enums\ShippingRateType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone_id' => $this->zone_id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'config_json' => $this->publicConfig(),
            'is_active' => $this->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicConfig(): array
    {
        $config = $this->config_json ?? [];
        $currency = strtoupper((string) data_get($config, 'currency', data_get($this->zone?->store, 'default_currency', 'EUR')));

        if (in_array($this->type, [ShippingRateType::Flat, ShippingRateType::Carrier], true)) {
            return [
                'price_amount' => (int) data_get($config, 'price_amount', data_get($config, 'amount', 0)),
                'currency' => $currency,
            ];
        }

        if ($this->type === ShippingRateType::Weight) {
            return [
                'currency' => $currency,
                'tiers' => collect(data_get($config, 'tiers', data_get($config, 'ranges', [])))
                    ->map(fn (array $tier): array => [
                        'min_weight_g' => (int) data_get($tier, 'min_weight_g', data_get($tier, 'min_g', 0)),
                        'max_weight_g' => data_get($tier, 'max_weight_g', data_get($tier, 'max_g')),
                        'price_amount' => (int) data_get($tier, 'price_amount', data_get($tier, 'amount', 0)),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return [
            'currency' => $currency,
            'tiers' => collect(data_get($config, 'tiers', data_get($config, 'ranges', [])))
                ->map(fn (array $tier): array => [
                    'min_order_amount' => (int) data_get($tier, 'min_order_amount', data_get($tier, 'min_amount', 0)),
                    'max_order_amount' => data_get($tier, 'max_order_amount', data_get($tier, 'max_amount')),
                    'price_amount' => (int) data_get($tier, 'price_amount', data_get($tier, 'amount', 0)),
                ])
                ->values()
                ->all(),
        ];
    }
}
