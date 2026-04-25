<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'order_id', 'provider', 'method', 'status', 'amount', 'reference', 'raw_payload_encrypted'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

