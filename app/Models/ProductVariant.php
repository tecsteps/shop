<?php

namespace App\Models;

use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'title', 'sku', 'barcode', 'price_amount', 'compare_at_amount', 'cost_amount', 'currency', 'weight_grams', 'weight_g', 'requires_shipping', 'is_default', 'position', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['requires_shipping' => 'boolean', 'is_default' => 'boolean', 'metadata' => 'array', 'status' => VariantStatus::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values', 'variant_id', 'product_option_value_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_lines', 'variant_id', 'order_id');
    }

    public function availableQuantity(): int
    {
        $inventory = $this->inventory;

        return $inventory ? $inventory->availableQuantity() : 0;
    }
}
