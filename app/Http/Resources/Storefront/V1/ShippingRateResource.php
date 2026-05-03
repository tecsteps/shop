<?php

namespace App\Http\Resources\Storefront\V1;

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
            'amount' => $this->when($this->getAttribute('calculated_amount') !== null, $this->getAttribute('calculated_amount')),
            'config' => $this->config_json,
        ];
    }
}
