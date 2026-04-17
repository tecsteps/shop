<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProductMedia
 */
class ProductMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value ?? $this->type,
            'storage_key' => $this->storage_key,
            'alt_text' => $this->alt_text,
            'width' => $this->width,
            'height' => $this->height,
            'mime_type' => $this->mime_type,
            'position' => (int) $this->position,
            'status' => $this->status?->value ?? $this->status,
        ];
    }
}
