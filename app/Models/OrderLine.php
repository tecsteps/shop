<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'title_snapshot',
        'variant_title_snapshot',
        'sku_snapshot',
        'quantity',
        'unit_price_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'requires_shipping',
        'fulfilled_quantity',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requires_shipping' => 'boolean',
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'fulfilled_quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
