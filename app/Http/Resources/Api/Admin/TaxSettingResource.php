<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'store_id' => $this->store_id,
            'mode' => $this->mode->value,
            'provider' => $this->provider->value,
            'prices_include_tax' => (bool) $this->prices_include_tax,
            'config_json' => $this->config_json,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
