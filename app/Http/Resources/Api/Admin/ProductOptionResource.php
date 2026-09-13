<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProductOption
 */
class ProductOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'values' => $this->whenLoaded('productOptionValues', fn () => $this->productOptionValues->map(fn ($value): array => [
                'id' => $value->id,
                'value' => $value->value,
                'position' => $value->position,
            ])->all(), []),
        ];
    }
}
