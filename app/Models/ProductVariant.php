<?php

namespace App\Models;

use App\Enums\ProductVariantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price_amount',
        'compare_at_amount',
        'currency',
        'weight_g',
        'requires_shipping',
        'is_default',
        'position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'requires_shipping' => 'boolean',
            'is_default' => 'boolean',
            'status' => ProductVariantStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'variant_option_values',
            'variant_id',
            'product_option_value_id',
        )
            ->using(VariantOptionValue::class);
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }
}
