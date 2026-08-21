<?php

namespace App\Models;

use App\Enums\InventoryPolicy;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'variant_id', 'quantity_on_hand', 'quantity_reserved', 'policy'];

    protected function casts(): array
    {
        return ['policy' => InventoryPolicy::class];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function availableQuantity(): int
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    public function canSell(int $quantity): bool
    {
        return $this->policy === InventoryPolicy::Continue || $this->availableQuantity() >= $quantity;
    }
}
