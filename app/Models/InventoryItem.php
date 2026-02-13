<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property InventoryPolicy $policy
 * @property int $quantity_on_hand
 * @property int $quantity_reserved
 */
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'policy' => InventoryPolicy::class,
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
        ];
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

    public function available(): int
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }
}
