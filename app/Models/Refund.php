<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = ['order_id', 'payment_id', 'amount', 'status', 'reason', 'restock', 'provider_refund_id', 'lines_json'];

    protected function casts(): array
    {
        return ['restock' => 'boolean', 'lines_json' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
