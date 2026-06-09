<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderLine extends Model
{
    /** @use HasFactory<\Database\Factories\OrderLineFactory> */
    use HasFactory;

    /**
     * The order_lines table has no timestamp columns.
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
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'total_amount' => 'integer',
            'tax_lines_json' => 'array',
            'discount_allocations_json' => 'array',
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

    public function fulfillmentLines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }

    /**
     * Units of this line already included in fulfillments.
     */
    public function fulfilledQuantity(): int
    {
        return (int) $this->fulfillmentLines()->sum('quantity');
    }

    /**
     * Units of this line not yet fulfilled.
     */
    public function unfulfilledQuantity(): int
    {
        return max(0, $this->quantity - $this->fulfilledQuantity());
    }
}
