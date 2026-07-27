<?php

namespace App\Http\Resources\Admin;

use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fulfillment payload for the Admin API (spec 02 §3.4).
 *
 * @mixin Fulfillment
 */
class FulfillmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('lines');

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status->value,
            'tracking_company' => $this->tracking_company,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at?->toIso8601ZuluString(),
            'line_items' => $this->lines->map(fn (FulfillmentLine $line): array => [
                'order_line_id' => $line->order_line_id,
                'quantity' => $line->quantity,
            ])->values()->all(),
        ];
    }
}
