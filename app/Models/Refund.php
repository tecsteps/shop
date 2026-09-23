<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'payment_id', 'amount', 'reason', 'status', 'provider_refund_id', 'created_at'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RefundLine::class);
    }
}
