<?php

namespace App\Http\Resources\Admin;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'store_id' => (int) $this->store_id,
            'title' => (string) $this->title,
            'handle' => (string) $this->handle,
            'status' => $this->enumValue($this->status),
            'description_html' => $this->description_html,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => is_array($this->tags) ? $this->tags : [],
            'published_at' => $this->published_at?->toISOString(),
            'variants_count' => isset($this->variants_count) ? (int) $this->variants_count : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
