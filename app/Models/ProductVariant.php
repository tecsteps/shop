<?php

namespace App\Models;

use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_shipping' => 'boolean',
            'is_default' => 'boolean',
            'status' => VariantStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values', 'variant_id', 'product_option_value_id');
    }

    /**
     * @return HasOne<InventoryItem, $this>
     */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    /**
     * @return HasMany<CartLine, $this>
     */
    public function cartLines(): HasMany
    {
        return $this->hasMany(CartLine::class, 'variant_id');
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class, 'variant_id');
    }
}
