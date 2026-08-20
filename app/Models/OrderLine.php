<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    protected $fillable = ['order_id', 'product_id', 'variant_id', 'product_title', 'title_snapshot', 'variant_title', 'sku', 'sku_snapshot', 'quantity', 'unit_price_amount', 'line_subtotal_amount', 'line_discount_amount', 'line_total_amount', 'tax_lines_json', 'discount_allocations_json'];

    protected function casts(): array
    {
        return ['tax_lines_json' => 'array', 'discount_allocations_json' => 'array'];
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
