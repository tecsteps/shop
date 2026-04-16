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

    public $timestamps = false;

    protected $fillable = ['store_id', 'variant_id', 'quantity_on_hand', 'quantity_reserved', 'policy'];

    protected function casts(): array
    {
        return [
            'policy' => InventoryPolicy::class,
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function available(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    public function canFulfill(int $quantity): bool
    {
        if ($this->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $this->available() >= $quantity;
    }
}
