<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'mode' => $this->mode->value,
            'provider' => $this->provider,
            'prices_include_tax' => $this->prices_include_tax,
            'config_json' => $this->config_json ?? [],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
