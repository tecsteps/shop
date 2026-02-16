<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    /** @use HasFactory<\Database\Factories\OrderLineFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'title_snapshot',
        'sku_snapshot',
        'variant_title_snapshot',
        'quantity',
        'unit_price',
        'subtotal',
        'total',
        'requires_shipping',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'subtotal' => 'integer',
            'total' => 'integer',
            'requires_shipping' => 'boolean',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
