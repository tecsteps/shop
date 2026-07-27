<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    /** @use HasFactory<\Database\Factories\OrderLineFactory> */
    use HasFactory;

    /**
     * The table has no timestamp columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_lines_json' => 'array',
            'discount_allocations_json' => 'array',
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    /**
     * Get the order that owns the line.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product the line references (null after product deletion).
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant the line references (null after variant deletion).
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Units of this line not yet covered by any fulfillment.
     */
    public function unfulfilledQuantity(): int
    {
        $fulfilled = FulfillmentLine::query()
            ->where('order_line_id', $this->id)
            ->sum('quantity');

        return max(0, $this->quantity - (int) $fulfilled);
    }
}
