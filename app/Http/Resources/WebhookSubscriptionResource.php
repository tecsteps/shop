<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WebhookSubscription
 */
class WebhookSubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'event_type' => $this->event_type,
            'target_url' => $this->target_url,
            'status' => $this->status,
            'consecutive_failures' => (int) $this->consecutive_failures,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
