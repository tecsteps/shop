<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('payment');

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_id' => $this->payment_id,
            'provider_refund_id' => $this->provider_refund_id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'payment_status' => $this->payment?->status->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
