<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('settings');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'version' => $this->version,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toISOString(),
            'settings_json' => $this->settings?->settings_json ?? [],
            'files_count' => $this->files_count ?? $this->files()->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
