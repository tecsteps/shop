<?php

namespace App\Http\Resources\Admin\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class TaxSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'store_id' => $this->store_id,
            'mode' => $this->mode?->value,
            'provider' => $this->provider,
            'prices_include_tax' => $this->prices_include_tax,
            'config_json' => $this->publicConfig(),
            'updated_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicConfig(): array
    {
        $config = $this->config_json ?? [];

        if ($this->mode?->value === 'provider') {
            return Arr::except($config, ['provider_api_key']);
        }

        return [
            ...Arr::except($config, ['default_rate_bps', 'rates', 'provider_api_key']),
            'default_tax_rate' => (int) data_get($config, 'default_tax_rate', data_get($config, 'default_rate_bps', 0)),
            'tax_rates' => collect(data_get($config, 'tax_rates', data_get($config, 'rates', [])))
                ->map(fn (array $rate): array => [
                    'country_code' => strtoupper((string) data_get($rate, 'country_code', data_get($rate, 'country'))),
                    'rate' => (int) data_get($rate, 'rate', data_get($rate, 'rate_bps', 0)),
                    'name' => (string) data_get($rate, 'name', 'Tax'),
                    'shipping_taxed' => (bool) data_get($rate, 'shipping_taxed', data_get($config, 'shipping_taxable', true)),
                ])
                ->values()
                ->all(),
        ];
    }
}
