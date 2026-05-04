<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'price_amount' => 0,
        'currency' => 'USD',
        'requires_shipping' => true,
        'is_default' => false,
        'position' => 0,
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('current_store', function (Builder $builder): void {
            if (! app()->bound('current_store')) {
                return;
            }

            $store = app('current_store');

            if (! $store instanceof Store) {
                return;
            }

            $builder->whereHas('product', function (Builder $query) use ($store): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey());
            });
        });

        static::saving(function (ProductVariant $variant): void {
            $variant->assertSkuIsUniqueForStore();
        });

        static::created(function (ProductVariant $variant): void {
            if ($variant->inventoryItem()->exists()) {
                return;
            }

            $storeId = Product::withoutGlobalScopes()
                ->whereKey($variant->product_id)
                ->value('store_id');

            if (! $storeId) {
                return;
            }

            InventoryItem::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'variant_id' => $variant->getKey(),
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ]);
        });
    }

    private function assertSkuIsUniqueForStore(): void
    {
        $sku = trim((string) $this->sku);

        if ($sku === '') {
            return;
        }

        $storeId = Product::withoutGlobalScopes()
            ->whereKey($this->product_id)
            ->value('store_id');

        if ($storeId === null) {
            return;
        }

        $query = self::withoutGlobalScopes()
            ->where('sku', $sku)
            ->whereHas('product', function (Builder $query) use ($storeId): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $storeId);
            });

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        if ($query->exists()) {
            throw new RuntimeException("The SKU [{$sku}] is already used in this store.");
        }
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasOne<InventoryItem, $this>
     */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    /**
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values', 'variant_id', 'product_option_value_id');
    }

    public function isPurchasable(): bool
    {
        return $this->status === VariantStatus::Active;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'compare_at_amount' => 'integer',
            'weight_g' => 'integer',
            'requires_shipping' => 'bool',
            'is_default' => 'bool',
            'position' => 'integer',
            'status' => VariantStatus::class,
        ];
    }
}
