<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Models\Concerns\BelongsToStore;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'policy',
    ];

    protected $attributes = [
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny->value,
    ];

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    protected function available(): Attribute
    {
        return Attribute::get(fn (): int => $this->quantity_on_hand - $this->quantity_reserved);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'policy' => InventoryPolicy::class,
        ];
    }
}
