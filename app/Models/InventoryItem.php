<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'policy',
    ];

    protected function casts(): array
    {
        return [
            'policy' => InventoryPolicy::class,
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getQuantityAvailableAttribute(): int
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }
}
