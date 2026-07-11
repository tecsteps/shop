<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    /** @use HasFactory<\Database\Factories\RefundFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'payment_id', 'amount', 'reason', 'status', 'provider_refund_id'];

    protected $attributes = ['amount' => 0, 'status' => 'pending'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    protected function casts(): array
    {
        return ['status' => RefundStatus::class];
    }
}
