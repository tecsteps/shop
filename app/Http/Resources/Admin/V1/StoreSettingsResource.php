<?php

namespace App\Http\Resources\Admin\V1;

use App\Models\StoreDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreSettingsResource extends JsonResource
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
            'name' => $this->name,
            'handle' => $this->handle,
            'status' => $this->status?->value,
            'default_currency' => $this->default_currency,
            'default_locale' => $this->default_locale,
            'timezone' => $this->timezone,
            'settings_json' => $this->settings?->settings_json ?? [],
            'settings_updated_at' => $this->settings?->updated_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'domains' => $this->whenLoaded('domains', fn (): array => $this->domains
                ->map(fn (StoreDomain $domain): array => [
                    'id' => $domain->id,
                    'hostname' => $domain->hostname,
                    'type' => $domain->type?->value,
                    'is_primary' => $domain->is_primary,
                    'tls_mode' => $domain->tls_mode,
                    'created_at' => $domain->created_at?->toIso8601String(),
                ])
                ->values()
                ->all()),
        ];
    }
}
