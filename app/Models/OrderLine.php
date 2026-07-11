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

    public $timestamps = false;

    protected $fillable = ['order_id', 'product_id', 'variant_id', 'title_snapshot', 'sku_snapshot', 'quantity', 'unit_price_amount', 'total_amount', 'tax_lines_json', 'discount_allocations_json'];

    protected $attributes = ['quantity' => 1, 'unit_price_amount' => 0, 'total_amount' => 0, 'tax_lines_json' => '[]', 'discount_allocations_json' => '[]'];

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

    protected function casts(): array
    {
        return ['tax_lines_json' => 'array', 'discount_allocations_json' => 'array'];
    }
}
