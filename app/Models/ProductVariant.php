<?php

namespace App\Models;

use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class ProductVariant extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant): void {
            $sku = trim((string) $variant->sku);
            if ($sku === '') {
                return;
            }

            $storeId = Product::withoutGlobalScopes()->whereKey($variant->product_id)->value('store_id');
            $duplicateExists = static::query()
                ->where('sku', $sku)
                ->when($variant->exists, fn (Builder $query) => $query->whereKeyNot($variant->getKey()))
                ->whereHas('product', fn (Builder $query) => $query->withoutGlobalScopes()->where('store_id', $storeId))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages(['sku' => 'The SKU has already been taken for this store.']);
            }

            $variant->sku = $sku;
        });

        static::created(function (ProductVariant $variant): void {
            $storeId = Product::withoutGlobalScopes()->whereKey($variant->product_id)->value('store_id');

            InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['variant_id' => $variant->getKey()],
                [
                    'store_id' => $storeId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ],
            );
        });
    }

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price_amount', 'compare_at_amount', 'currency', 'weight_g',
        'requires_shipping', 'is_default', 'position', 'status',
    ];

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values', 'variant_id', 'product_option_value_id')
            ->using(VariantOptionValue::class);
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    public function cartLines(): HasMany
    {
        return $this->hasMany(CartLine::class, 'variant_id');
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class, 'variant_id');
    }
}
