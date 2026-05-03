<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FulfillmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('lines.orderLine');

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status->value,
            'tracking_company' => $this->tracking_company,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'line_items' => $this->lines
                ->map(fn ($line): array => [
                    'order_line_id' => $line->order_line_id,
                    'quantity' => $line->quantity,
                    'title_snapshot' => $line->orderLine?->title_snapshot,
                    'sku_snapshot' => $line->orderLine?->sku_snapshot,
                ])
                ->values()
                ->all(),
        ];
    }
}
