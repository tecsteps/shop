<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Exceptions\DuplicateSkuException;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

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

    protected $attributes = [
        'price_amount' => 0,
        'currency' => 'USD',
        'requires_shipping' => true,
        'is_default' => false,
        'position' => 0,
        'status' => VariantStatus::Active->value,
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasOne<InventoryItem, $this> */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    /** @return BelongsToMany<ProductOptionValue, $this> */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values', 'variant_id', 'product_option_value_id');
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant): void {
            $variant->sku = filled($variant->sku) ? trim((string) $variant->sku) : null;

            if ($variant->sku === null || $variant->product_id === null) {
                return;
            }

            $storeId = Product::withoutGlobalScopes()->whereKey($variant->product_id)->value('store_id');

            $duplicateExists = ProductVariant::withoutGlobalScopes()
                ->where('sku', $variant->sku)
                ->when($variant->exists, fn (Builder $query): Builder => $query->whereKeyNot($variant->getKey()))
                ->whereHas('product', fn (Builder $query): Builder => $query->withoutGlobalScopes()->where('store_id', $storeId))
                ->exists();

            if ($duplicateExists) {
                throw new DuplicateSkuException($variant->sku);
            }
        });

        static::created(function (ProductVariant $variant): void {
            $storeId = Product::withoutGlobalScopes()->whereKey($variant->product_id)->valueOrFail('store_id');

            InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['variant_id' => $variant->getKey()],
                [
                    'store_id' => $storeId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny,
                ],
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'compare_at_amount' => 'integer',
            'weight_g' => 'integer',
            'requires_shipping' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'integer',
            'status' => VariantStatus::class,
        ];
    }
}
