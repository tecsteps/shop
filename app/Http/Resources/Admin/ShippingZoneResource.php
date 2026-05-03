<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('rates');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'countries_json' => $this->countries_json ?? [],
            'regions_json' => $this->regions_json ?? [],
            'rates' => $this->rates
                ->map(fn ($rate): array => [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'type' => $rate->type->value,
                    'config_json' => $rate->config_json ?? [],
                    'is_active' => $rate->is_active,
                ])
                ->values()
                ->all(),
        ];
    }
}
