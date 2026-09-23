<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['refund_id', 'order_line_id', 'quantity', 'amount'];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }
}
