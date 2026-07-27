<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
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
     * Get the attributes that should be cast.
     *
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
     * Get the product that owns the variant.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the inventory item tracking stock for the variant.
     *
     * @return HasOne<InventoryItem, $this>
     */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    /**
     * Get the option values that define the variant.
     *
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values', 'variant_id', 'product_option_value_id');
    }

    /**
     * Build the display title from the option values, e.g. "Blue / Medium".
     */
    public function title(): string
    {
        $values = $this->optionValues
            ->sortBy(fn (ProductOptionValue $value) => $value->option->position)
            ->pluck('value');

        return $values->isEmpty() ? 'Default' : $values->implode(' / ');
    }

    /**
     * Available stock: on hand minus reserved.
     */
    public function availableQuantity(): int
    {
        return $this->inventoryItem?->available() ?? 0;
    }

    /**
     * Whether the variant has stock available to sell.
     */
    public function isInStock(): bool
    {
        return $this->availableQuantity() > 0;
    }

    /**
     * Whether the variant may be sold below zero stock (backorder policy).
     */
    public function isBackorderable(): bool
    {
        return $this->inventoryItem?->policy === InventoryPolicy::Continue;
    }
}
