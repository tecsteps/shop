<?php

namespace App\Http\Resources\Admin\V1;

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
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'countries_json' => $this->countries_json ?? [],
            'regions_json' => $this->regions_json ?? [],
            'rates' => ShippingRateResource::collection($this->whenLoaded('rates')),
        ];
    }
}
