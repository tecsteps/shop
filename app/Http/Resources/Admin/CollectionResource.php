<?php

namespace App\Http\Resources\Admin;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Collection
 */
class CollectionResource extends JsonResource
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
            'description_html' => $this->description_html,
            'type' => $this->enumValue($this->type),
            'status' => $this->enumValue($this->status),
            'products_count' => isset($this->products_count) ? (int) $this->products_count : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
