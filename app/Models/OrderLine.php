<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property int|null $variant_id
 * @property string $title_snapshot
 * @property string|null $sku_snapshot
 * @property int $quantity
 * @property int $unit_price_amount
 * @property int $total_amount
 * @property array<int, mixed> $tax_lines_json
 * @property array<int, mixed> $discount_allocations_json
 * @property-read Order $order
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class OrderLine extends Model
{
    /** @use HasFactory<\Database\Factories\OrderLineFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'title_snapshot',
        'sku_snapshot',
        'quantity',
        'unit_price_amount',
        'total_amount',
        'tax_lines_json',
        'discount_allocations_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'total_amount' => 'integer',
            'tax_lines_json' => 'array',
            'discount_allocations_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
