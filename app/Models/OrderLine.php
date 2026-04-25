<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    protected $fillable = ['order_id', 'product_variant_id', 'title', 'sku', 'quantity', 'unit_price_amount', 'total_amount', 'snapshot_json'];

    protected function casts(): array
    {
        return ['snapshot_json' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

