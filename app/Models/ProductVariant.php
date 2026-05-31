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
     * Auto-provision the variant's inventory record on creation.
     *
     * Each variant has exactly one inventory item. We create it here (rather
     * than via an observer) so it happens whether the variant is made through a
     * service, a factory, or a seeder. The inventory store_id is copied from the
     * owning product so it stays correct even when no current_store is bound.
     */
    protected static function booted(): void
    {
        static::created(function (self $variant): void {
            if ($variant->inventoryItem()->exists()) {
                return;
            }

            $storeId = Product::withoutGlobalScopes()
                ->whereKey($variant->product_id)
                ->value('store_id');

            $variant->inventoryItem()->create([
                'store_id' => $storeId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny->value,
            ]);
        });
    }

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
            'price_amount' => 'integer',
            'compare_at_amount' => 'integer',
            'weight_g' => 'integer',
            'requires_shipping' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'integer',
            'status' => VariantStatus::class,
        ];
    }

    /**
     * The product this variant belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The inventory record for this variant.
     *
     * @return HasOne<InventoryItem, $this>
     */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id');
    }

    /**
     * The option values that define this variant (e.g. Size:L + Color:Red).
     *
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'variant_option_values',
            'variant_id',
            'product_option_value_id',
        );
    }
}
