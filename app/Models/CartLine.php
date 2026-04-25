<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartLine extends Model
{
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity', 'unit_price_amount', 'snapshot_json'];

    protected function casts(): array
    {
        return ['snapshot_json' => 'array'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

