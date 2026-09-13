<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'type' => $this->type->value,
            'storage_key' => $this->storage_key,
            'url' => Storage::disk('public')->url($this->storage_key),
            'alt_text' => $this->alt_text,
            'width' => $this->width,
            'height' => $this->height,
            'mime_type' => $this->mime_type,
            'byte_size' => $this->byte_size,
            'position' => $this->position,
            'status' => $this->status->value,
        ];
    }
}
