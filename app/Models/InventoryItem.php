<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'policy',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ];

    protected static function booted(): void
    {
        static::saving(function (InventoryItem $item): void {
            $storeId = $item->variantStoreId();

            if ($storeId === null) {
                return;
            }

            if (! $item->store_id) {
                $item->store_id = $storeId;

                return;
            }

            if ((int) $item->store_id !== $storeId) {
                throw new InvalidArgumentException('Inventory item store must match the variant product store.');
            }
        });
    }

    private function variantStoreId(): ?int
    {
        $variant = ProductVariant::withoutGlobalScopes()
            ->select(['id', 'product_id'])
            ->find($this->variant_id);

        if (! $variant instanceof ProductVariant) {
            return null;
        }

        $storeId = Product::withoutGlobalScopes()
            ->whereKey($variant->product_id)
            ->value('store_id');

        return $storeId === null ? null : (int) $storeId;
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function availableQuantity(): int
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'policy' => InventoryPolicy::class,
        ];
    }
}
