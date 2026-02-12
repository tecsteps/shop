<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartLine extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'cart_id',
        'variant_id',
        'product_title',
        'variant_title',
        'sku',
        'quantity',
        'unit_price_amount',
        'line_subtotal_amount',
        'line_discount_amount',
        'line_total_amount',
        'image_url',
        'requires_shipping',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'line_subtotal_amount' => 'integer',
            'line_discount_amount' => 'integer',
            'line_total_amount' => 'integer',
            'requires_shipping' => 'boolean',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
