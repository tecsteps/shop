<?php

namespace App\Http\Resources\Admin;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Refund
 */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'order_id' => $this->order_id,
            'payment_id' => $this->payment_id,
            'provider_refund_id' => $this->provider_refund_id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
