<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Store
 */
class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'handle' => $this->handle,
            'status' => $this->status->value,
            'default_currency' => $this->default_currency,
            'default_locale' => $this->default_locale,
            'timezone' => $this->timezone,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
