<?php

namespace App\Http\Resources\Admin;

use App\Models\Fulfillment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin fulfillment representation: status, tracking, and fulfilled line items.
 *
 * @mixin Fulfillment
 */
class FulfillmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status->value,
            'tracking_company' => $this->tracking_company,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at?->toISOString(),
            'line_items' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'order_line_id' => $line->order_line_id,
                'quantity' => $line->quantity,
            ])->values()),
        ];
    }
}
