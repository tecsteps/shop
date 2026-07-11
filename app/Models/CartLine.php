<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartLine extends Model
{
    /** @use HasFactory<\Database\Factories\CartLineFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['cart_id', 'variant_id', 'quantity', 'unit_price_amount', 'line_subtotal_amount', 'line_discount_amount', 'line_total_amount'];

    protected $attributes = ['quantity' => 1, 'unit_price_amount' => 0, 'line_subtotal_amount' => 0, 'line_discount_amount' => 0, 'line_total_amount' => 0];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
